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
            'stats' => [
                'total_revenue' => $this->getTotalRevenue(),
                'total_invoices' => $this->getTotalInvoices(),
                'pending_payments' => $this->getPendingPayments(),
                'active_patients' => $this->getActivePatients(),
            ],
            'recent_invoices' => $this->getRecentInvoices(),
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

    private function getPendingPayments(): int
    {
        return Payment::where('status', 'pending')->count();
    }

    private function getActivePatients(): int
    {
        return Patient::whereHas('visits', function ($query) {
            $query->where('created_at', '>=', now()->subMonths(6));
        })->count();
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
}
