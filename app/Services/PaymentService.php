<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Http\Requests\ProcessPaymentRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentService
{
    public function processPayment(Invoice $invoice, ProcessPaymentRequest $request): Payment
    {
        return DB::transaction(function () use ($invoice, $request) {
            // CRITICAL: Lock the invoice row for concurrency protection
            $lockedInvoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            
            // Calculate remaining balance safely
            $totalPaid = Payment::where('invoice_id', $invoice->id)
                               ->where('status', 'confirmed')
                               ->sum('amount');
            
            $remainingBalance = $lockedInvoice->patient_responsibility - $totalPaid;

            if ($request->amount > $remainingBalance) {
                throw new Exception('Payment amount exceeds remaining balance');
            }

            $paymentData = [
                'invoice_id' => $invoice->id,
                'payment_method' => $request->method,
                'amount' => $request->amount,
                'status' => 'pending',
                'notes' => $request->notes,
                'cashier_id' => auth()->id(),
            ];

            if ($request->method === 'cash') {
                $paymentData['status'] = 'confirmed';
                $paymentData['confirmed_at'] = now();
            } elseif ($request->method === 'mobile_money') {
                $paymentData['transaction_ref'] = $this->initiateMobileMoneyPayment([
                    'amount' => $request->amount,
                    'phone_number' => $request->phone,
                    'invoice_id' => $invoice->id,
                ]);
            }

            $payment = Payment::create($paymentData);

            if ($payment->status === 'confirmed') {
                $lockedInvoice->updateStatus();
            }

            return $payment;
        });
    }

    public function getPaymentStatus(int $paymentId): ?Payment
    {
        return Payment::with('invoice')->find($paymentId);
    }

    public function confirmMobileMoneyPayment(array $payload, WebhookEvent $webhookEvent): void
    {
        DB::transaction(function () use ($payload, $webhookEvent) {
            $payment = Payment::where('transaction_ref', $payload['orderNumber'])
                              ->where('status', 'pending')
                              ->first();

            if (!$payment) {
                throw new Exception('Payment not found or already processed');
            }

            if ($payload['status'] === 'PAYMENT_COMPLETE') {
                $payment->confirm();
                $payment->webhook_event_id = $webhookEvent->id;
                $payment->save();
            } elseif ($payload['status'] === 'PAYMENT_FAILED') {
                $payment->status = 'failed';
                $payment->webhook_event_id = $webhookEvent->id;
                $payment->save();
            }

            $webhookEvent->markAsProcessed();
        });
    }

    private function initiateMobileMoneyPayment(array $data): string
    {
        // Mock implementation for testing - in production, integrate with real eFichePay API
        if (app()->environment('local', 'testing')) {
            $transactionRef = 'MOCK-' . uniqid() . '-' . time();
            
            // Simulate successful mobile money payment initiation
            Log::info('Mock mobile money payment initiated', [
                'transaction_ref' => $transactionRef,
                'amount' => $data['amount'],
                'phone' => $data['phone_number'],
                'invoice_id' => $data['invoice_id'],
                'callback_url' => route('webhooks.efichepay'),
            ]);
            
            return $transactionRef;
        }

        // Production implementation
        $response = Http::post('https://api.efichepay.rw/payments', [
            'amount' => $data['amount'] * 100, // Convert to cents
            'phone' => $data['phone_number'],
            'merchant_id' => config('services.efichepay.merchant_id'),
            'callback_url' => route('webhooks.efichepay'),
            'order_number' => 'PAY-' . uniqid(),
        ]);

        if (!$response->successful()) {
            throw new Exception('Mobile money payment initiation failed: ' . $response->body());
        }

        return $response->json()['transaction_ref'];
    }

    public function validatePaymentAmount(Invoice $invoice, float $amount): bool
    {
        $totalPaid = $invoice->confirmedPayments()->sum('amount');
        $remainingBalance = $invoice->patient_responsibility - $totalPaid;
        
        return $amount <= $remainingBalance && $amount > 0;
    }

    public function getAllPayments()
    {
        return Payment::with(['invoice.visit.patient'])->get();
    }

    public function getPaymentById(int $id): ?Payment
    {
        return Payment::with(['invoice.visit.patient'])->find($id);
    }

    public function getPaymentSummary(?string $dateFrom = null, ?string $dateTo = null): array
    {
        // Build base query with date filtering
        $baseQuery = Payment::query();
        
        if ($dateFrom) {
            $baseQuery->whereDate('created_at', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $baseQuery->whereDate('created_at', '<=', $dateTo);
        }

        // Get facility ID for security (from authenticated user)
        $userFacilityId = auth()->user()?->facility_id;
        
        if ($userFacilityId) {
            $baseQuery->whereHas('invoice.visit', function ($query) use ($userFacilityId) {
                $query->where('facility_id', $userFacilityId);
            });
        }

        // Core metrics
        $totalPayments = $baseQuery->count();
        $completedPayments = (clone $baseQuery)->where('status', 'confirmed')->count();
        $totalRevenue = (clone $baseQuery)->where('status', 'confirmed')->sum('amount');
        $pendingAmount = (clone $baseQuery)->where('status', 'pending')->sum('amount');

        // Payment methods breakdown with totals
        $paymentMethods = ['cash', 'mobile_money', 'insurance'];
        $paymentMethodsBreakdown = [];
        
        foreach ($paymentMethods as $method) {
            $methodQuery = clone $baseQuery;
            $count = $methodQuery->where('payment_method', $method)->count();
            $total = $methodQuery->where('payment_method', $method)->sum('amount');
            
            $paymentMethodsBreakdown[$method] = [
                'count' => $count,
                'total' => (float) $total
            ];
        }

        // Invoice and patient metrics
        $invoiceQuery = \App\Models\Invoice::query();
        if ($userFacilityId) {
            $invoiceQuery->whereHas('visit', function ($query) use ($userFacilityId) {
                $query->where('facility_id', $userFacilityId);
            });
        }
        
        if ($dateFrom) {
            $invoiceQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $invoiceQuery->whereDate('created_at', '<=', $dateTo);
        }

        $totalInvoices = $invoiceQuery->count();
        
        // Patient metrics
        $patientQuery = \App\Models\Patient::query();
        if ($userFacilityId) {
            $patientQuery->whereHas('visits', function ($query) use ($userFacilityId) {
                $query->where('facility_id', $userFacilityId);
            });
        }
        
        $totalPatients = $patientQuery->count();
        
        // Active patients (patients with visits in date range)
        $activePatientsQuery = \App\Models\Patient::query()
            ->whereHas('visits', function ($query) use ($dateFrom, $dateTo, $userFacilityId) {
                if ($userFacilityId) {
                    $query->where('facility_id', $userFacilityId);
                }
                if ($dateFrom) {
                    $query->whereDate('created_at', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $query->whereDate('created_at', '<=', $dateTo);
                }
            });
            
        $activePatients = $activePatientsQuery->count();
        
        // New patients in date range
        $newPatientsQuery = \App\Models\Patient::query();
        if ($userFacilityId) {
            $newPatientsQuery->whereHas('visits', function ($query) use ($userFacilityId) {
                $query->where('facility_id', $userFacilityId);
            });
        }
        
        if ($dateFrom) {
            $newPatientsQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $newPatientsQuery->whereDate('created_at', '<=', $dateTo);
        }
        
        $newPatients = $newPatientsQuery->count();
        
        // Growth calculations
        $patientGrowthRate = $totalPatients > 0 ? ($newPatients / $totalPatients) * 100 : 0;
        $patientRetentionRate = $activePatients > 0 ? (($activePatients - $newPatients) / $activePatients) * 100 : 0;
        
        // Revenue trends (daily data for charts)
        $revenueTrends = $this->getRevenueTrends($dateFrom, $dateTo, $userFacilityId);
        
        // Growth comparison with previous period
        $growthComparison = $this->calculateGrowthComparison($dateFrom, $dateTo, $userFacilityId);

        return [
            'total_revenue' => (float) $totalRevenue,
            'total_payments' => $totalPayments,
            'total_invoices' => $totalInvoices,
            'total_patients' => $totalPatients,
            'active_patients' => $activePatients,
            'new_patients' => $newPatients,
            'patient_growth_rate' => round($patientGrowthRate, 2),
            'patient_retention_rate' => round($patientRetentionRate, 2),
            'payment_methods_breakdown' => $paymentMethodsBreakdown,
            'revenue_trends' => $revenueTrends,
            'growth_comparison' => $growthComparison,
        ];
    }

    public function getPaymentsWithFilters(?string $search = null, ?string $status = null, ?string $method = null, int $page = 1, int $limit = 20, ?int $userFacilityId = null): array
    {
        $query = Payment::with(['invoice.visit.patient'])
            ->when($userFacilityId, function ($q, $facilityId) {
                // Security: Only show payments from user's facility
                return $q->whereHas('invoice.visit', function ($subQuery) use ($facilityId) {
                    $subQuery->where('facility_id', $facilityId);
                });
            })
            ->when($status, function ($q, $status) {
                return $q->where('status', $status);
            })
            ->when($method, function ($q, $method) {
                return $q->where('payment_method', $method);
            })
            ->when($search, function ($q, $search) {
                return $q->where(function ($subQuery) use ($search) {
                    $subQuery->where('transaction_ref', 'LIKE', "%{$search}%")
                            ->orWhereHas('invoice', function ($invoiceQuery) use ($search) {
                                $invoiceQuery->where('invoice_number', 'LIKE', "%{$search}%");
                            })
                            ->orWhereHas('invoice.visit.patient', function ($patientQuery) use ($search) {
                                $patientQuery->where('first_name', 'LIKE', "%{$search}%")
                                           ->orWhere('last_name', 'LIKE', "%{$search}%");
                            });
                });
            });

        // Get total count for pagination
        $total = $query->count();

        // Get paginated results
        $payments = $query->orderBy('created_at', 'desc')
                          ->offset(($page - 1) * $limit)
                          ->limit($limit)
                          ->get();

        return [
            'payments' => $payments,
            'total' => $total,
        ];
    }

    public function updatePaymentStatus(int $paymentId, string $status): Payment
    {
        return DB::transaction(function () use ($paymentId, $status) {
            $payment = Payment::lockForUpdate()->findOrFail($paymentId);
            
            $payment->status = $status;
            
            if ($status === 'confirmed') {
                $payment->confirmed_at = now();
            }
            
            $payment->save();
            
            // Update invoice status if needed
            $invoice = $payment->invoice;
            $totalPaid = $invoice->payments()->where('status', 'confirmed')->sum('amount');
            
            if ($totalPaid >= $invoice->total_amount) {
                $invoice->status = 'paid';
                $invoice->save();
            } elseif ($totalPaid > 0) {
                $invoice->status = 'partially_paid';
                $invoice->save();
            }
            
            return $payment;
        });
    }

    public function deletePayment(int $paymentId): void
    {
        DB::transaction(function () use ($paymentId) {
            $payment = Payment::lockForUpdate()->findOrFail($paymentId);
            
            // Only allow deletion of pending payments
            if ($payment->status !== 'pending') {
                throw new Exception('Cannot delete confirmed or failed payments');
            }
            
            $invoice = $payment->invoice;
            
            // Delete the payment
            $payment->delete();
            
            // Update invoice status
            $totalPaid = $invoice->payments()->where('status', 'confirmed')->sum('amount');
            
            if ($totalPaid >= $invoice->total_amount) {
                $invoice->status = 'paid';
            } elseif ($totalPaid > 0) {
                $invoice->status = 'partially_paid';
            } else {
                $invoice->status = 'pending';
            }
            
            $invoice->save();
        });
    }

    public function retryPayment(int $paymentId): Payment
    {
        return DB::transaction(function () use ($paymentId) {
            $payment = Payment::lockForUpdate()->findOrFail($paymentId);
            
            // Only allow retry of failed payments
            if ($payment->status !== 'failed') {
                throw new Exception('Can only retry failed payments');
            }
            
            // Reset payment status and generate new transaction reference
            $payment->status = 'pending';
            $payment->transaction_ref = $this->generateTransactionReference();
            $payment->confirmed_at = null;
            $payment->save();
            
            // If it's a mobile money payment, re-initiate
            if ($payment->payment_method === 'mobile_money') {
                $newTransactionRef = $this->initiateMobileMoneyPayment([
                    'amount' => $payment->amount,
                    'phone_number' => $payment->phone,
                    'invoice_id' => $payment->invoice_id,
                ]);
                
                $payment->transaction_ref = $newTransactionRef;
                $payment->save();
            }
            
            return $payment;
        });
    }

    private function generateTransactionReference(): string
    {
        return 'PAY-' . strtoupper(uniqid()) . '-' . time();
    }

    /**
     * Get revenue trends for chart data
     */
    public function getRevenueTrends(?string $dateFrom = null, ?string $dateTo = null, ?int $userFacilityId = null, string $groupBy = 'daily'): array
    {
        $query = \App\Models\Payment::where('status', 'confirmed');
        
        if ($userFacilityId) {
            $query->whereHas('invoice.visit', function ($subQuery) use ($userFacilityId) {
                $subQuery->where('facility_id', $userFacilityId);
            });
        }
        
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
        
        switch ($groupBy) {
            case 'weekly':
                $revenueData = $query->selectRaw('YEAR(created_at) as year, WEEK(created_at) as week, SUM(amount) as revenue')
                    ->groupBy('year', 'week')
                    ->orderBy('year')
                    ->orderBy('week')
                    ->get();
                    
                $trends = $revenueData->map(function ($item) {
                    return [
                        'period' => "Week {$item->week}",
                        'revenue' => (float) $item->revenue
                    ];
                })->toArray();
                break;
                
            case 'monthly':
                $revenueData = $query->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(amount) as revenue')
                    ->groupBy('year', 'month')
                    ->orderBy('year')
                    ->orderBy('month')
                    ->get();
                    
                $trends = $revenueData->map(function ($item) {
                    $monthName = \Carbon\Carbon::create($item->year, $item->month)->format('F');
                    return [
                        'period' => $monthName,
                        'revenue' => (float) $item->revenue
                    ];
                })->toArray();
                break;
                
            case 'daily':
            default:
                $revenueData = $query->selectRaw('DATE(created_at) as date, SUM(amount) as revenue')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
                    
                $trends = $revenueData->map(function ($item) {
                    return [
                        'period' => $item->date,
                        'revenue' => (float) $item->revenue
                    ];
                })->toArray();
                break;
        }
        
        return [
            'trends' => $trends,
            'labels' => array_column($trends, 'period'),
            'data' => array_column($trends, 'revenue')
        ];
    }

    /**
     * Calculate growth comparison with previous period
     */
    private function calculateGrowthComparison(?string $dateFrom = null, ?string $dateTo = null, ?int $userFacilityId = null): array
    {
        // Default to last 30 days if no date range provided
        if (!$dateFrom) {
            $dateFrom = now()->subDays(30)->toDateString();
        }
        if (!$dateTo) {
            $dateTo = now()->toDateString();
        }
        
        $currentPeriodStart = \Carbon\Carbon::parse($dateFrom);
        $currentPeriodEnd = \Carbon\Carbon::parse($dateTo);
        $daysDiff = $currentPeriodStart->diffInDays($currentPeriodEnd);
        
        // Previous period (same duration)
        $previousPeriodStart = $currentPeriodStart->copy()->subDays($daysDiff);
        $previousPeriodEnd = $currentPeriodStart->copy()->subDay();
        
        // Current period metrics
        $currentRevenue = $this->getRevenueInPeriod($dateFrom, $dateTo, $userFacilityId);
        $currentPayments = $this->getPaymentsInPeriod($dateFrom, $dateTo, $userFacilityId);
        $currentInvoices = $this->getInvoicesInPeriod($dateFrom, $dateTo, $userFacilityId);
        $currentPatients = $this->getActivePatientsInPeriod($dateFrom, $dateTo, $userFacilityId);
        
        // Previous period metrics
        $previousRevenue = $this->getRevenueInPeriod($previousPeriodStart->toDateString(), $previousPeriodEnd->toDateString(), $userFacilityId);
        $previousPayments = $this->getPaymentsInPeriod($previousPeriodStart->toDateString(), $previousPeriodEnd->toDateString(), $userFacilityId);
        $previousInvoices = $this->getInvoicesInPeriod($previousPeriodStart->toDateString(), $previousPeriodEnd->toDateString(), $userFacilityId);
        $previousPatients = $this->getActivePatientsInPeriod($previousPeriodStart->toDateString(), $previousPeriodEnd->toDateString(), $userFacilityId);
        
        return [
            'revenue_growth' => $previousRevenue > 0 ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2) : 0,
            'payments_growth' => $previousPayments > 0 ? round((($currentPayments - $previousPayments) / $previousPayments) * 100, 2) : 0,
            'invoices_growth' => $previousInvoices > 0 ? round((($currentInvoices - $previousInvoices) / $previousInvoices) * 100, 2) : 0,
            'patients_growth' => $previousPatients > 0 ? round((($currentPatients - $previousPatients) / $previousPatients) * 100, 2) : 0,
        ];
    }

    private function getRevenueInPeriod(string $dateFrom, string $dateTo, ?int $userFacilityId = null): float
    {
        $query = \App\Models\Payment::where('status', 'confirmed')
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);
            
        if ($userFacilityId) {
            $query->whereHas('invoice.visit', function ($subQuery) use ($userFacilityId) {
                $subQuery->where('facility_id', $userFacilityId);
            });
        }
        
        return (float) $query->sum('amount');
    }

    private function getPaymentsInPeriod(string $dateFrom, string $dateTo, ?int $userFacilityId = null): int
    {
        $query = \App\Models\Payment::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);
            
        if ($userFacilityId) {
            $query->whereHas('invoice.visit', function ($subQuery) use ($userFacilityId) {
                $subQuery->where('facility_id', $userFacilityId);
            });
        }
        
        return $query->count();
    }

    private function getInvoicesInPeriod(string $dateFrom, string $dateTo, ?int $userFacilityId = null): int
    {
        $query = \App\Models\Invoice::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);
            
        if ($userFacilityId) {
            $query->whereHas('visit', function ($subQuery) use ($userFacilityId) {
                $subQuery->where('facility_id', $userFacilityId);
            });
        }
        
        return $query->count();
    }

    private function getActivePatientsInPeriod(string $dateFrom, string $dateTo, ?int $userFacilityId = null): int
    {
        $query = \App\Models\Patient::whereHas('visits', function ($subQuery) use ($dateFrom, $dateTo, $userFacilityId) {
            $subQuery->whereDate('created_at', '>=', $dateFrom)
                     ->whereDate('created_at', '<=', $dateTo);
                     
            if ($userFacilityId) {
                $subQuery->where('facility_id', $userFacilityId);
            }
        });
        
        return $query->count();
    }
}
