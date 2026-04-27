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

    public function getPaymentSummary(): array
    {
        $totalPayments = Payment::count();
        $completedPayments = Payment::where('status', 'confirmed')->count();
        $totalRevenue = Payment::where('status', 'confirmed')->sum('amount');
        $pendingAmount = Payment::where('status', 'pending')->sum('amount');

        // Payment methods breakdown
        $paymentMethods = ['cash', 'mobile_money', 'insurance'];
        $paymentMethodsBreakdown = [];
        
        foreach ($paymentMethods as $method) {
            $count = Payment::where('payment_method', $method)->count();
            $paymentMethodsBreakdown[$method] = $count;
        }

        // Monthly stats
        $currentMonth = now()->startOfMonth();
        $previousMonth = now()->subMonth()->startOfMonth();
        
        $monthlyStats = [
            'current_month' => [
                'payments' => Payment::where('created_at', '>=', $currentMonth)->count(),
                'revenue' => Payment::where('status', 'confirmed')
                    ->where('created_at', '>=', $currentMonth)
                    ->sum('amount'),
            ],
            'previous_month' => [
                'payments' => Payment::where('created_at', '>=', $previousMonth)
                    ->where('created_at', '<', $currentMonth)
                    ->count(),
                'revenue' => Payment::where('status', 'confirmed')
                    ->where('created_at', '>=', $previousMonth)
                    ->where('created_at', '<', $currentMonth)
                    ->sum('amount'),
            ],
        ];

        return [
            'total_payments' => $totalPayments,
            'completed_payments' => $completedPayments,
            'total_revenue' => (float) $totalRevenue,
            'pending_amount' => (float) $pendingAmount,
            'payment_methods_breakdown' => $paymentMethodsBreakdown,
            'monthly_stats' => $monthlyStats,
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
}
