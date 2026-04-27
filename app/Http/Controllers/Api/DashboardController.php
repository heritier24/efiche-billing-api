<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Exception;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->dashboardService->getDashboardStats();
            
            return response()->json($stats);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve dashboard stats',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getPaymentStats(): JsonResponse
    {
        try {
            $stats = $this->dashboardService->getPaymentStats();
            
            return response()->json($stats);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve payment stats',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getTopPayingPatients(): JsonResponse
    {
        try {
            $patients = $this->dashboardService->getTopPayingPatients();
            
            return response()->json($patients);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve top paying patients',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getActivePatients(): JsonResponse
    {
        try {
            $activePatients = $this->dashboardService->getActivePatientsData();
            
            return response()->json($activePatients);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve active patients',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getRevenueSummary(): JsonResponse
    {
        try {
            $revenueSummary = $this->dashboardService->getRevenueSummary();
            
            return response()->json($revenueSummary);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve revenue summary',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getRecentInvoices(): JsonResponse
    {
        try {
            $limit = request()->get('limit', 5);
            $recentInvoices = $this->dashboardService->getRecentInvoicesData($limit);
            
            return response()->json($recentInvoices);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve recent invoices',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
