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
}
