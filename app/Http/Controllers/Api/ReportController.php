<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportsService;
use App\Http\Requests\Reports\SummaryReportRequest;
use App\Http\Requests\Reports\PaymentMethodsRequest;
use App\Http\Requests\Reports\RevenueAnalyticsRequest;
use App\Http\Requests\Reports\InvoiceAnalyticsRequest;
use App\Http\Requests\Reports\PatientAnalyticsRequest;
use App\Http\Requests\Reports\ExportReportRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\Request;
use Exception;

class ReportController extends Controller
{
    protected ReportsService $reportsService;

    public function __construct(ReportsService $reportsService)
    {
        $this->reportsService = $reportsService;
    }

    /**
     * Get comprehensive summary statistics for dashboard
     */
    public function getSummary(SummaryReportRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $data = $this->reportsService->getReportsSummary(
                dateFrom: $validated['date_from'],
                dateTo: $validated['date_to'],
                facilityId: $validated['facility_id'] ?? $request->user()->facility_id,
                cashierId: $validated['cashier_id'] ?? null
            );
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate summary report',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment method breakdown analytics
     */
    public function getPaymentMethods(PaymentMethodsRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $data = $this->reportsService->getPaymentMethods(
                dateFrom: $validated['date_from'],
                dateTo: $validated['date_to'],
                groupBy: $validated['group_by'] ?? null,
                facilityId: $validated['facility_id'] ?? $request->user()->facility_id
            );
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate payment methods report',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get revenue analytics with trends
     */
    public function getRevenueAnalytics(RevenueAnalyticsRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $data = $this->reportsService->getRevenueAnalytics(
                dateFrom: $validated['date_from'],
                dateTo: $validated['date_to'],
                granularity: $validated['granularity'] ?? 'daily',
                facilityId: $validated['facility_id'] ?? $request->user()->facility_id
            );
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate revenue analytics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get invoice analytics and aging
     */
    public function getInvoiceAnalytics(InvoiceAnalyticsRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $data = $this->reportsService->getInvoiceAnalytics(
                dateFrom: $validated['date_from'],
                dateTo: $validated['date_to'],
                statusFilter: $validated['status_filter'] ?? null,
                agingDays: $validated['aging_days'] ?? 30,
                facilityId: $validated['facility_id'] ?? $request->user()->facility_id
            );
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate invoice analytics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get patient analytics and demographics
     */
    public function getPatientAnalytics(PatientAnalyticsRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $data = $this->reportsService->getPatientAnalytics(
                dateFrom: $validated['date_from'],
                dateTo: $validated['date_to'],
                groupBy: $validated['group_by'] ?? null,
                facilityId: $validated['facility_id'] ?? $request->user()->facility_id
            );
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate patient analytics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export reports in various formats
     */
    public function exportReport(ExportReportRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $data = $this->reportsService->exportReport(
                reportType: $validated['report_type'],
                dateFrom: $validated['date_from'],
                dateTo: $validated['date_to'],
                format: $validated['format'],
                filters: $validated['filters'] ?? null,
                facilityId: $validated['facility_id'] ?? $request->user()->facility_id
            );
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Report generated successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to export report',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download exported report file
     */
    public function downloadExport(string $fileName): BinaryFileResponse|JsonResponse
    {
        try {
            $filePath = storage_path("app/exports/{$fileName}");
            
            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'error' => 'File not found',
                    'message' => 'Export file has expired or does not exist'
                ], 404);
            }
            
            // Check if file has expired (24 hours)
            $fileModified = filemtime($filePath);
            $expiryTime = $fileModified + (24 * 60 * 60); // 24 hours
            
            if (time() > $expiryTime) {
                unlink($filePath); // Delete expired file
                return response()->json([
                    'success' => false,
                    'error' => 'File expired',
                    'message' => 'Export file has expired'
                ], 410);
            }
            
            return response()->download($filePath, $fileName, [
                'Content-Type' => $this->getContentType($fileName),
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to download file',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get batch reports for performance optimization
     */
    public function getBatchReports(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'report_ids' => 'required|array|string',
                'report_ids.*' => 'string|in:summary,payment-methods,revenue,invoices,patients',
                'date_from' => 'required|date',
                'date_to' => 'required|date|after:date_from',
                'facility_id' => 'nullable|integer|exists:facilities,id'
            ]);

            $facilityId = $validated['facility_id'] ?? $request->user()->facility_id;
            $reports = [];

            foreach ($validated['report_ids'] as $reportId) {
                switch ($reportId) {
                    case 'summary':
                        $reports[$reportId] = $this->reportsService->getReportsSummary(
                            $validated['date_from'], $validated['date_to'], $facilityId
                        );
                        break;
                    case 'payment-methods':
                        $reports[$reportId] = $this->reportsService->getPaymentMethods(
                            $validated['date_from'], $validated['date_to'], null, $facilityId
                        );
                        break;
                    case 'revenue':
                        $reports[$reportId] = $this->reportsService->getRevenueAnalytics(
                            $validated['date_from'], $validated['date_to'], 'daily', $facilityId
                        );
                        break;
                    case 'invoices':
                        $reports[$reportId] = $this->reportsService->getInvoiceAnalytics(
                            $validated['date_from'], $validated['date_to'], null, 30, $facilityId
                        );
                        break;
                    case 'patients':
                        $reports[$reportId] = $this->reportsService->getPatientAnalytics(
                            $validated['date_from'], $validated['date_to'], null, $facilityId
                        );
                        break;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $reports
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate batch reports',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time report updates (WebSocket endpoint placeholder)
     */
    public function getRealTimeUpdates(Request $request): JsonResponse
    {
        try {
            // This would be implemented with WebSocket/SSE in production
            // For now, return current stats
            $data = $this->reportsService->getReportsSummary(
                dateFrom: now()->subDays(30)->toDateString(),
                dateTo: now()->toDateString(),
                facilityId: $request->user()->facility_id
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'type' => 'metric_update',
                    'data' => [
                        'metric' => 'total_revenue',
                        'value' => $data['total_revenue'],
                        'timestamp' => now()->toISOString()
                    ]
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get real-time updates',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search reports with advanced filtering
     */
    public function searchReports(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'query' => 'required|string|max:255',
                'date_from' => 'required|date',
                'date_to' => 'required|date|after:date_from',
                'report_types' => 'nullable|array|string',
                'report_types.*' => 'string|in:summary,payment-methods,revenue,invoices,patients',
                'facility_id' => 'nullable|integer|exists:facilities,id'
            ]);

            $facilityId = $validated['facility_id'] ?? $request->user()->facility_id;
            $results = [];

            // Simple search implementation - in production, use full-text search
            $reportTypes = $validated['report_types'] ?? ['summary', 'payment-methods', 'revenue', 'invoices', 'patients'];
            
            foreach ($reportTypes as $reportType) {
                $results[$reportType] = [
                    'type' => $reportType,
                    'available' => true,
                    'description' => $this->getReportDescription($reportType)
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'query' => $validated['query'],
                    'results' => $results,
                    'total' => count($results)
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to search reports',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Schedule report export
     */
    public function scheduleExport(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'report_type' => 'required|string|in:summary,revenue,payments,invoices,patients',
                'date_from' => 'required|date',
                'date_to' => 'required|date|after:date_from',
                'format' => 'required|string|in:pdf,excel,csv',
                'schedule_type' => 'required|string|in:once,daily,weekly,monthly',
                'schedule_time' => 'nullable|string',
                'email_recipients' => 'nullable|array|string',
                'email_recipients.*' => 'email',
                'facility_id' => 'nullable|integer|exists:facilities,id'
            ]);

            // This would integrate with a job queue in production
            $scheduleId = 'sched_' . uniqid() . '_' . time();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'schedule_id' => $scheduleId,
                    'status' => 'scheduled',
                    'next_run' => $this->calculateNextRun($validated['schedule_type'], $validated['schedule_time'] ?? null),
                    'report_type' => $validated['report_type'],
                    'format' => $validated['format']
                ],
                'message' => 'Report export scheduled successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to schedule export',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get content type based on file extension
     */
    private function getContentType(string $fileName): string
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        return match ($extension) {
            'csv' => 'text/csv',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
    }

    /**
     * Get report description
     */
    private function getReportDescription(string $reportType): string
    {
        return match ($reportType) {
            'summary' => 'Comprehensive summary statistics for dashboard',
            'payment-methods' => 'Detailed breakdown of payments by method',
            'revenue' => 'Revenue analytics with trends and forecasts',
            'invoices' => 'Invoice analytics and aging reports',
            'patients' => 'Patient analytics and demographics',
            default => 'Unknown report type',
        };
    }

    /**
     * Calculate next run time for scheduled exports
     */
    private function calculateNextRun(string $scheduleType, ?string $scheduleTime): string
    {
        $time = $scheduleTime ? explode(':', $scheduleTime) : [9, 0]; // Default 9:00 AM
        
        return match ($scheduleType) {
            'once' => now()->setTime($time[0], $time[1])->addMinutes(5)->toISOString(),
            'daily' => now()->addDay()->setTime($time[0], $time[1])->toISOString(),
            'weekly' => now()->addWeek()->setTime($time[0], $time[1])->toISOString(),
            'monthly' => now()->addMonth()->setTime($time[0], $time[1])->toISOString(),
            default => now()->addDay()->toISOString(),
        };
    }
}
