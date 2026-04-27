<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getDashboardStats(): array
    {
        return [
            'total_invoices' => $this->getTotalInvoices(),
            'total_revenue' => $this->getTotalRevenue(),
            'pending_invoices' => $this->getPendingInvoices(),
            'active_patients' => $this->getActivePatients(),
            'monthly_stats' => $this->getMonthlyStats(),
            'payment_methods_breakdown' => $this->getPaymentMethodsBreakdown(),
        ];
    }

    private function getTotalRevenue(): float
    {
        return Payment::where('status', 'confirmed')
                     ->sum('amount');
    }

    private function getTotalInvoices(): int
    {
        return Invoice::count();
    }

    private function getPendingInvoices(): int
    {
        return Invoice::whereIn('status', ['pending', 'partially_paid'])->count();
    }

    private function getActivePatients(): int
    {
        return Patient::whereHas('visits', function ($query) {
            $query->where('created_at', '>=', now()->subDays(30));
        })->count();
    }

    private function getMonthlyStats(): array
    {
        $currentMonth = now()->startOfMonth();
        $previousMonth = now()->subMonth()->startOfMonth();
        
        return [
            'current_month' => [
                'invoices' => Invoice::where('created_at', '>=', $currentMonth)->count(),
                'revenue' => Payment::where('status', 'confirmed')
                    ->where('created_at', '>=', $currentMonth)
                    ->sum('amount'),
                'patients' => Patient::whereHas('visits', function ($query) use ($currentMonth) {
                    $query->where('created_at', '>=', $currentMonth);
                })->count(),
            ],
            'previous_month' => [
                'invoices' => Invoice::where('created_at', '>=', $previousMonth)
                    ->where('created_at', '<', $currentMonth)
                    ->count(),
                'revenue' => Payment::where('status', 'confirmed')
                    ->where('created_at', '>=', $previousMonth)
                    ->where('created_at', '<', $currentMonth)
                    ->sum('amount'),
                'patients' => Patient::whereHas('visits', function ($query) use ($previousMonth, $currentMonth) {
                    $query->where('created_at', '>=', $previousMonth)
                        ->where('created_at', '<', $currentMonth);
                })->count(),
            ],
        ];
    }

    private function getPaymentMethodsBreakdown(): array
    {
        $totalPayments = Payment::where('status', 'confirmed')->count();
        
        if ($totalPayments === 0) {
            return ['cash' => 0, 'mobile_money' => 0, 'insurance' => 0];
        }
        
        $cashCount = Payment::where('status', 'confirmed')->where('payment_method', 'cash')->count();
        $mobileCount = Payment::where('status', 'confirmed')->where('payment_method', 'mobile_money')->count();
        $insuranceCount = Payment::where('status', 'confirmed')->where('payment_method', 'insurance')->count();
        
        return [
            'cash' => round(($cashCount / $totalPayments) * 100),
            'mobile_money' => round(($mobileCount / $totalPayments) * 100),
            'insurance' => round(($insuranceCount / $totalPayments) * 100),
        ];
    }

    private function getRecentInvoices(): array
    {
        return Invoice::with(['visit.patient'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'patient_name' => $invoice->visit->patient->full_name,
                    'total_amount' => $invoice->total_amount,
                    'status' => $invoice->status,
                    'created_at' => $invoice->created_at->toISOString(),
                ];
            })
            ->toArray();
    }

    public function getPaymentStats(): array
    {
        return [
            'cash_payments' => $this->getPaymentStatsByMethod('cash'),
            'mobile_money_payments' => $this->getPaymentStatsByMethod('mobile_money'),
            'insurance_payments' => $this->getPaymentStatsByMethod('insurance'),
            'daily_revenue' => $this->getDailyRevenue(30),
            'monthly_revenue' => $this->getMonthlyRevenue(12),
        ];
    }

    private function getPaymentStatsByMethod(string $method): array
    {
        $query = Payment::where('payment_method', $method);
        
        return [
            'total_count' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'confirmed_count' => $query->where('status', 'confirmed')->count(),
            'confirmed_amount' => $query->where('status', 'confirmed')->sum('amount'),
            'pending_count' => $query->where('status', 'pending')->count(),
            'pending_amount' => $query->where('status', 'pending')->sum('amount'),
        ];
    }

    private function getDailyRevenue(int $days): array
    {
        return Payment::where('status', 'confirmed')
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, SUM(amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'revenue' => (float) $item->revenue,
                ];
            })
            ->toArray();
    }

    private function getMonthlyRevenue(int $months): array
    {
        return Payment::where('status', 'confirmed')
            ->where('created_at', '>=', now()->subMonths($months))
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as revenue')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => $item->month,
                    'revenue' => (float) $item->revenue,
                ];
            })
            ->toArray();
    }

    public function getTopPayingPatients(int $limit = 10): array
    {
        return Patient::with(['visits.invoices.payments' => function ($query) {
            $query->where('status', 'confirmed');
        }])
        ->whereHas('visits.invoices.payments', function ($query) {
            $query->where('status', 'confirmed');
        })
        ->get()
        ->map(function ($patient) {
            $totalPaid = $patient->visits
                ->flatMap->invoices
                ->flatMap->payments
                ->where('status', 'confirmed')
                ->sum('amount');
                
            return [
                'id' => $patient->id,
                'name' => $patient->full_name,
                'total_paid' => $totalPaid,
                'payment_count' => $patient->visits
                    ->flatMap->invoices
                    ->flatMap->payments
                    ->where('status', 'confirmed')
                    ->count(),
            ];
        })
        ->sortByDesc('total_paid')
        ->take($limit)
        ->values()
        ->toArray();
    }

    public function getActivePatientsData(): array
    {
        $thirtyDaysAgo = now()->subDays(30);
        
        $totalActivePatients = Patient::whereHas('visits', function ($query) use ($thirtyDaysAgo) {
            $query->where('created_at', '>=', $thirtyDaysAgo);
        })->count();
        
        $newPatients = Patient::where('created_at', '>=', $thirtyDaysAgo)->count();
        $returningPatients = $totalActivePatients - $newPatients;
        
        $patientsWithVisits = Patient::whereHas('visits', function ($query) use ($thirtyDaysAgo) {
            $query->where('created_at', '>=', $thirtyDaysAgo);
        })->count();
        
        $patientsWithPayments = Patient::whereHas('visits.invoice.payments', function ($query) use ($thirtyDaysAgo) {
            $query->where('payments.created_at', '>=', $thirtyDaysAgo)
                  ->where('payments.status', 'confirmed');
        })->count();
        
        return [
            'active_patients' => $totalActivePatients,
            'period' => '30_days',
            'breakdown' => [
                'new_patients' => $newPatients,
                'returning_patients' => $returningPatients,
                'patients_with_visits' => $patientsWithVisits,
                'patients_with_payments' => $patientsWithPayments,
            ],
        ];
    }

    public function getRevenueSummary(): array
    {
        $period = request()->get('period', '30d');
        $days = $period === '30d' ? 30 : ($period === '7d' ? 7 : 30);
        
        $startDate = now()->subDays($days);
        $previousStartDate = now()->subDays($days * 2);
        
        $currentRevenue = Payment::where('status', 'confirmed')
            ->where('created_at', '>=', $startDate)
            ->sum('amount');
            
        $previousRevenue = Payment::where('status', 'confirmed')
            ->where('created_at', '>=', $previousStartDate)
            ->where('created_at', '<', $startDate)
            ->sum('amount');
            
        $growthRate = $previousRevenue > 0 ? 
            (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;
            
        $dailyAverage = $days > 0 ? $currentRevenue / $days : 0;
        
        // Payment method breakdown
        $paymentMethods = ['cash', 'mobile_money', 'insurance'];
        $byPaymentMethod = [];
        
        foreach ($paymentMethods as $method) {
            $amount = Payment::where('status', 'confirmed')
                ->where('payment_method', $method)
                ->where('created_at', '>=', $startDate)
                ->sum('amount');
                
            $count = Payment::where('status', 'confirmed')
                ->where('payment_method', $method)
                ->where('created_at', '>=', $startDate)
                ->count();
                
            $byPaymentMethod[$method] = [
                'amount' => $amount,
                'count' => $count,
                'percentage' => $currentRevenue > 0 ? round(($amount / $currentRevenue) * 100) : 0,
            ];
        }
        
        // Daily breakdown
        $dailyBreakdown = Payment::where('status', 'confirmed')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(amount) as revenue, COUNT(*) as transactions')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'revenue' => (float) $item->revenue,
                    'transactions' => $item->transactions,
                ];
            })
            ->toArray();
        
        return [
            'total_revenue' => $currentRevenue,
            'period' => $days . '_days',
            'daily_average' => $dailyAverage,
            'growth_rate' => round($growthRate, 2),
            'by_payment_method' => $byPaymentMethod,
            'daily_breakdown' => $dailyBreakdown,
        ];
    }

    public function getRecentInvoicesData(int $limit = 5): array
    {
        $invoices = Invoice::with(['visit.patient', 'lineItems'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
            
        $data = $invoices->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'visit_id' => $invoice->visit_id,
                'status' => $invoice->status,
                'total_amount' => (float) $invoice->total_amount,
                'total_paid' => (float) $invoice->total_paid,
                'remaining_balance' => (float) ($invoice->total_amount - $invoice->total_paid),
                'created_at' => $invoice->created_at->toISOString(),
                'visit' => [
                    'id' => $invoice->visit->id,
                    'patient' => [
                        'id' => $invoice->visit->patient->id,
                        'first_name' => $invoice->visit->patient->first_name ?? '',
                        'last_name' => $invoice->visit->patient->last_name ?? '',
                        'full_name' => $invoice->visit->patient->full_name,
                    ],
                ],
                'line_items' => $invoice->lineItems->map(function ($item) {
                    return [
                        'item_code' => $item->item_code,
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'total_price' => (float) ($item->quantity * $item->unit_price),
                    ];
                })->toArray(),
            ];
        });
        
        return [
            'data' => $data->toArray(),
            'total' => Invoice::count(),
        ];
    }
}
