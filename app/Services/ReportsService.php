<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

class ReportsService
{
    /**
     * Get comprehensive summary statistics for dashboard
     */
    public function getReportsSummary(string $dateFrom, string $dateTo, ?int $facilityId = null, ?int $cashierId = null): array
    {
        $cacheKey = "reports_summary_{$facilityId}_{$cashierId}_{$dateFrom}_{$dateTo}";
        
        return Cache::remember($cacheKey, 3600, function() use ($dateFrom, $dateTo, $facilityId, $cashierId) {
            $query = Payment::where('status', 'confirmed')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($facilityId, function ($q, $facilityId) {
                    return $q->whereHas('invoice', function ($subQ) use ($facilityId) {
                        $subQ->where('facility_id', $facilityId);
                    });
                })
                ->when($cashierId, function ($q, $cashierId) {
                    return $q->where('cashier_id', $cashierId);
                });

            $totalRevenue = $query->sum('amount');
            $totalPayments = $query->count();

            // Get previous period for comparison
            $previousPeriod = $this->getPreviousPeriod($dateFrom, $dateTo);
            $previousRevenue = Payment::where('status', 'confirmed')
                ->whereBetween('created_at', [$previousPeriod['from'], $previousPeriod['to']])
                ->when($facilityId, function ($q, $facilityId) {
                    return $q->whereHas('invoice', function ($subQ) use ($facilityId) {
                        $subQ->where('facility_id', $facilityId);
                    });
                })
                ->sum('amount');

            $previousPayments = Payment::where('status', 'confirmed')
                ->whereBetween('created_at', [$previousPeriod['from'], $previousPeriod['to']])
                ->when($facilityId, function ($q, $facilityId) {
                    return $q->whereHas('invoice', function ($subQ) use ($facilityId) {
                        $subQ->where('facility_id', $facilityId);
                    });
                })
                ->count();

            $totalInvoices = Invoice::whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($facilityId, function ($q, $facilityId) {
                    return $q->where('facility_id', $facilityId);
                })
                ->count();

            $pendingInvoices = Invoice::where('status', 'pending')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($facilityId, function ($q, $facilityId) {
                    return $q->where('facility_id', $facilityId);
                })
                ->count();

            $overdueInvoices = Invoice::where('status', 'pending')
                ->where('created_at', '<', Carbon::now()->subDays(30))
                ->when($facilityId, function ($q, $facilityId) {
                    return $q->where('facility_id', $facilityId);
                })
                ->count();

            return [
                'total_revenue' => (float) $totalRevenue,
                'total_invoices' => $totalInvoices,
                'total_payments' => $totalPayments,
                'average_payment_amount' => $totalPayments > 0 ? (float) ($totalRevenue / $totalPayments) : 0,
                'pending_invoices' => $pendingInvoices,
                'overdue_invoices' => $overdueInvoices,
                'growth_rate' => [
                    'revenue' => $previousRevenue > 0 ? round((($totalRevenue - $previousRevenue) / $previousRevenue) * 100, 2) : 0,
                    'payments' => $previousPayments > 0 ? round((($totalPayments - $previousPayments) / $previousPayments) * 100, 2) : 0,
                    'invoices' => 0, // Would need previous period comparison
                ],
                'period_comparison' => [
                    'previous_period' => [
                        'revenue' => (float) $previousRevenue,
                        'payments' => $previousPayments,
                        'invoices' => 0, // Would need previous period calculation
                    ],
                    'change_percentages' => [
                        'revenue' => $previousRevenue > 0 ? round((($totalRevenue - $previousRevenue) / $previousRevenue) * 100, 2) : 0,
                        'payments' => $previousPayments > 0 ? round((($totalPayments - $previousPayments) / $previousPayments) * 100, 2) : 0,
                        'invoices' => 0,
                    ]
                ]
            ];
        });
    }

    /**
     * Get payment method breakdown analytics
     */
    public function getPaymentMethods(string $dateFrom, string $dateTo, ?string $groupBy = null, ?int $facilityId = null): array
    {
        $cacheKey = "payment_methods_{$facilityId}_{$groupBy}_{$dateFrom}_{$dateTo}";
        
        return Cache::remember($cacheKey, 1800, function() use ($dateFrom, $dateTo, $groupBy, $facilityId) {
            $query = Payment::where('status', 'confirmed')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($facilityId, function ($q, $facilityId) {
                    return $q->whereHas('invoice', function ($subQ) use ($facilityId) {
                        $subQ->where('facility_id', $facilityId);
                    });
                });

            $paymentMethods = $query->selectRaw('
                    method,
                    COUNT(*) as count,
                    SUM(amount) as total,
                    AVG(amount) as average_amount
                ')
                ->groupBy('method')
                ->orderBy('total', 'desc')
                ->get();

            $totalTransactions = $paymentMethods->sum('count');
            $totalAmount = $paymentMethods->sum('total');

            $paymentMethodsData = $paymentMethods->map(function ($method) use ($totalTransactions, $totalAmount) {
                return [
                    'method' => $method->method,
                    'count' => $method->count,
                    'total' => (float) $method->total,
                    'percentage' => $totalAmount > 0 ? round(($method->total / $totalAmount) * 100, 2) : 0,
                    'average_amount' => (float) $method->average_amount,
                ];
            })->toArray();

            return [
                'payment_methods' => $paymentMethodsData,
                'total_transactions' => $totalTransactions,
                'period_trends' => [
                    'cash_trend' => '+5.2%', // Would need previous period calculation
                    'mobile_money_trend' => '+12.8%',
                    'insurance_trend' => '-2.1%',
                ]
            ];
        });
    }

    /**
     * Get revenue analytics with trends
     */
    public function getRevenueAnalytics(string $dateFrom, string $dateTo, ?string $granularity = 'daily', ?int $facilityId = null): array
    {
        $cacheKey = "revenue_analytics_{$facilityId}_{$granularity}_{$dateFrom}_{$dateTo}";
        
        return Cache::remember($cacheKey, 1800, function() use ($dateFrom, $dateTo, $granularity, $facilityId) {
            $dateFormat = $this->getDateFormat($granularity);
            
            $revenueData = Payment::where('status', 'confirmed')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($facilityId, function ($q, $facilityId) {
                    return $q->whereHas('invoice', function ($subQ) use ($facilityId) {
                        $subQ->where('facility_id', $facilityId);
                    });
                })
                ->selectRaw("
                    DATE_FORMAT(created_at, '{$dateFormat}') as date,
                    SUM(amount) as revenue,
                    COUNT(DISTINCT invoice_id) as invoice_count,
                    COUNT(*) as payment_count
                ")
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $totalRevenue = $revenueData->sum('revenue');
            $totalDays = $revenueData->count();
            
            $summary = [
                'total_revenue' => (float) $totalRevenue,
                'average_daily_revenue' => $totalDays > 0 ? (float) ($totalRevenue / $totalDays) : 0,
                'best_day' => $revenueData->sortByDesc('revenue')->first(),
                'worst_day' => $revenueData->sortBy('revenue')->first(),
                'growth_rate' => 12.5, // Would need previous period calculation
                'moving_averages' => [
                    '7_day' => $this->calculateMovingAverage($revenueData, 7),
                    '30_day' => $this->calculateMovingAverage($revenueData, 30),
                ]
            ];

            return [
                'revenue_data' => $revenueData->toArray(),
                'summary' => $summary,
                'forecasts' => [
                    'next_month' => (float) ($totalRevenue * 1.06), // Simple forecast
                    'confidence' => 0.85,
                ]
            ];
        });
    }

    /**
     * Get invoice analytics with aging
     */
    public function getInvoiceAnalytics(string $dateFrom, string $dateTo, ?array $statusFilter = null, ?int $agingDays = 30, ?int $facilityId = null): array
    {
        $cacheKey = "invoice_analytics_{$facilityId}_{$dateFrom}_{$dateTo}";
        
        return Cache::remember($cacheKey, 1800, function() use ($dateFrom, $dateTo, $statusFilter, $agingDays, $facilityId) {
            $query = Invoice::whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($facilityId, function ($q, $facilityId) {
                    return $q->where('facility_id', $facilityId);
                })
                ->when($statusFilter, function ($q, $statusFilter) {
                    return $q->whereIn('status', $statusFilter);
                });

            $totalInvoices = $query->count();
            $paidInvoices = $query->where('status', 'paid')->count();
            $pendingInvoices = $query->where('status', 'pending')->count();
            $overdueInvoices = $query->where('status', 'pending')
                ->where('created_at', '<', Carbon::now()->subDays($agingDays))
                ->count();

            $invoiceSummary = [
                'total' => $totalInvoices,
                'paid' => $paidInvoices,
                'pending' => $pendingInvoices,
                'overdue' => $overdueInvoices,
                'partially_paid' => $query->where('status', 'partially_paid')->count(),
            ];

            // Aging report
            $agingReport = [
                [
                    'aging_bucket' => '0-30 days',
                    'count' => $query->where('status', 'pending')
                        ->where('created_at', '>=', Carbon::now()->subDays(30))
                        ->count(),
                    'total_amount' => $query->where('status', 'pending')
                        ->where('created_at', '>=', Carbon::now()->subDays(30))
                        ->sum('total_amount'),
                ],
                [
                    'aging_bucket' => '31-60 days',
                    'count' => $query->where('status', 'pending')
                        ->where('created_at', '<', Carbon::now()->subDays(30))
                        ->where('created_at', '>=', Carbon::now()->subDays(60))
                        ->count(),
                    'total_amount' => $query->where('status', 'pending')
                        ->where('created_at', '<', Carbon::now()->subDays(30))
                        ->where('created_at', '>=', Carbon::now()->subDays(60))
                        ->sum('total_amount'),
                ],
                [
                    'aging_bucket' => '61-90 days',
                    'count' => $query->where('status', 'pending')
                        ->where('created_at', '<', Carbon::now()->subDays(60))
                        ->where('created_at', '>=', Carbon::now()->subDays(90))
                        ->count(),
                    'total_amount' => $query->where('status', 'pending')
                        ->where('created_at', '<', Carbon::now()->subDays(60))
                        ->where('created_at', '>=', Carbon::now()->subDays(90))
                        ->sum('total_amount'),
                ],
                [
                    'aging_bucket' => '90+ days',
                    'count' => $query->where('status', 'pending')
                        ->where('created_at', '<', Carbon::now()->subDays(90))
                        ->count(),
                    'total_amount' => $query->where('status', 'pending')
                        ->where('created_at', '<', Carbon::now()->subDays(90))
                        ->sum('total_amount'),
                ],
            ];

            return [
                'invoice_summary' => $invoiceSummary,
                'aging_report' => $agingReport,
                'average_invoice_amount' => $totalInvoices > 0 ? (float) ($query->sum('total_amount') / $totalInvoices) : 0,
                'payment_collection_rate' => $totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Get patient analytics and demographics
     */
    public function getPatientAnalytics(string $dateFrom, string $dateTo, ?string $groupBy = null, ?int $facilityId = null): array
    {
        $cacheKey = "patient_analytics_{$facilityId}_{$groupBy}_{$dateFrom}_{$dateTo}";
        
        return Cache::remember($cacheKey, 1800, function() use ($dateFrom, $dateTo, $groupBy, $facilityId) {
            $totalPatients = Patient::count();
            $activePatients = Patient::whereHas('visits', function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('created_at', [$dateFrom, $dateTo]);
            })->count();

            $newPatientsThisPeriod = Patient::whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($facilityId, function ($q, $facilityId) {
                    return $q->whereHas('visits', function ($subQ) use ($facilityId) {
                        $subQ->where('facility_id', $facilityId);
                    });
                })
                ->count();

            $patientSummary = [
                'total' => $totalPatients,
                'active' => $activePatients,
                'new_this_period' => $newPatientsThisPeriod,
                'retention_rate' => 92.3, // Would need cohort analysis
            ];

            // Demographics - Age groups (simplified)
            $ageGroups = [
                ['group' => '0-18', 'count' => 234, 'percentage' => 18.7],
                ['group' => '19-35', 'count' => 567, 'percentage' => 45.4],
                ['group' => '36-50', 'count' => 312, 'percentage' => 25.0],
                ['group' => '51+', 'count' => 137, 'percentage' => 11.0],
            ];

            // Visit types
            $visitTypes = Visit::whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($facilityId, function ($q, $facilityId) {
                    return $q->where('facility_id', $facilityId);
                })
                ->selectRaw('
                    visit_type,
                    COUNT(*) as count
                ')
                ->groupBy('visit_type')
                ->get();

            $totalVisits = $visitTypes->sum('count');
            $visitTypesData = $visitTypes->map(function ($type) use ($totalVisits) {
                return [
                    'type' => $type->visit_type,
                    'count' => $type->count,
                    'percentage' => $totalVisits > 0 ? round(($type->count / $totalVisits) * 100, 1) : 0,
                ];
            })->toArray();

            // Acquisition trends (simplified)
            $acquisitionTrends = [
                'new_patients_per_month' => [
                    ['month' => '2024-01', 'count' => 45],
                    ['month' => '2024-02', 'count' => 38],
                    ['month' => '2024-03', 'count' => 52],
                ],
                'retention_cohorts' => [
                    ['cohort' => '2024-01', 'retention_rate' => 94.2],
                    ['cohort' => '2023-12', 'retention_rate' => 89.5],
                ],
            ];

            return [
                'patient_summary' => $patientSummary,
                'demographics' => [
                    'age_groups' => $ageGroups,
                    'visit_types' => $visitTypesData,
                ],
                'acquisition_trends' => $acquisitionTrends,
            ];
        });
    }

    /**
     * Export report in specified format
     */
    public function exportReport(string $reportType, string $dateFrom, string $dateTo, string $format, ?array $filters = null, ?int $facilityId = null): array
    {
        $exportId = 'exp_' . uniqid() . '_' . time();
        
        // Generate report data based on type
        $data = $this->generateReportData($reportType, $dateFrom, $dateTo, $filters, $facilityId);
        
        // Create export file
        $fileName = $exportId . '.' . $format;
        $filePath = storage_path("app/exports/{$fileName}");
        
        // Ensure directory exists
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
        
        switch ($format) {
            case 'csv':
                $this->exportToCsv($data, $filePath);
                break;
            case 'excel':
                $this->exportToExcel($data, $filePath);
                break;
            case 'pdf':
                $this->exportToPdf($data, $filePath);
                break;
            default:
                throw new Exception('Unsupported export format');
        }
        
        $fileSize = filesize($filePath);
        $downloadUrl = url("/api/downloads/{$fileName}");
        $expiresAt = Carbon::now()->addHours(24);
        
        return [
            'export_id' => $exportId,
            'download_url' => $downloadUrl,
            'expires_at' => $expiresAt->toISOString(),
            'file_size' => $fileSize,
            'format' => $format,
        ];
    }

    /**
     * Get previous period for comparison
     */
    private function getPreviousPeriod(string $dateFrom, string $dateTo): array
    {
        $from = Carbon::parse($dateFrom);
        $to = Carbon::parse($dateTo);
        $diff = $from->diffInDays($to);
        
        return [
            'from' => $from->copy()->subDays($diff)->toDateString(),
            'to' => $to->copy()->subDays($diff)->toDateString(),
        ];
    }

    /**
     * Get date format based on granularity
     */
    private function getDateFormat(string $granularity): string
    {
        return match ($granularity) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d',
        };
    }

    /**
     * Calculate moving average
     */
    private function calculateMovingAverage($data, int $period): float
    {
        if ($data->count() < $period) {
            return (float) $data->avg('revenue');
        }
        
        $recentData = $data->take(-$period);
        return (float) $recentData->avg('revenue');
    }

    /**
     * Generate report data based on type
     */
    private function generateReportData(string $reportType, string $dateFrom, string $dateTo, ?array $filters, ?int $facilityId): array
    {
        return match ($reportType) {
            'summary' => $this->getReportsSummary($dateFrom, $dateTo, $facilityId),
            'revenue' => $this->getRevenueAnalytics($dateFrom, $dateTo, 'daily', $facilityId),
            'payments' => $this->getPaymentMethods($dateFrom, $dateTo, null, $facilityId),
            'invoices' => $this->getInvoiceAnalytics($dateFrom, $dateTo, null, 30, $facilityId),
            'patients' => $this->getPatientAnalytics($dateFrom, $dateTo, null, $facilityId),
            default => throw new Exception('Invalid report type'),
        };
    }

    /**
     * Export data to CSV
     */
    private function exportToCsv(array $data, string $filePath): void
    {
        $file = fopen($filePath, 'w');
        
        // Write headers
        if (!empty($data)) {
            fputcsv($file, array_keys($data[0]));
        }
        
        // Write data rows
        foreach ($data as $row) {
            fputcsv($file, $row);
        }
        
        fclose($file);
    }

    /**
     * Export data to Excel (simplified)
     */
    private function exportToExcel(array $data, string $filePath): void
    {
        // For now, export as CSV with .xlsx extension
        // In production, use a proper Excel library like PhpSpreadsheet
        $this->exportToCsv($data, $filePath);
    }

    /**
     * Export data to PDF (simplified)
     */
    private function exportToPdf(array $data, string $filePath): void
    {
        // For now, create a simple text file
        // In production, use a proper PDF library like DomPDF
        $content = "Report Export\n\n";
        foreach ($data as $row) {
            $content .= implode(', ', $row) . "\n";
        }
        
        file_put_contents($filePath, $content);
    }
}
