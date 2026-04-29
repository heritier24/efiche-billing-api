<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\VisitController;
use App\Http\Controllers\Api\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public authentication endpoints
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected authentication endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    
    // Invoice endpoints (protected)
    Route::post('/visits/{visitId}/invoices', [InvoiceController::class, 'createInvoiceForVisit']);
    Route::get('/visits/{visitId}/invoice', [InvoiceController::class, 'getInvoiceByVisit']);
    Route::get('/invoices/pending', [InvoiceController::class, 'getPendingInvoices']);
    Route::apiResource('invoices', InvoiceController::class);
    
    // Payment endpoints (protected)
    Route::post('/invoices/{invoiceId}/payments', [PaymentController::class, 'processPayment']);
    Route::get('/payments/summary', [PaymentController::class, 'getPaymentSummary']);
    Route::get('/payments/revenue-trends', [PaymentController::class, 'getRevenueTrends']);
    Route::get('/payments/{paymentId}/status', [PaymentController::class, 'getPaymentStatus']);
    Route::put('/payments/{paymentId}/status', [PaymentController::class, 'updateStatus']);
    Route::delete('/payments/{paymentId}', [PaymentController::class, 'destroy']);
    Route::post('/payments/{paymentId}/retry', [PaymentController::class, 'retryPayment']);
    Route::apiResource('payments', PaymentController::class);
    
    // Visit endpoints (protected)
    Route::get('/visits', [VisitController::class, 'index']);
    Route::post('/visits', [VisitController::class, 'store']);
    Route::get('/visits/{id}', [VisitController::class, 'show']);
    Route::put('/visits/{id}/status', [VisitController::class, 'updateStatus']);
    Route::get('/visits/statistics', [VisitController::class, 'getStatistics']);
    
    // Patient endpoints (protected)
    Route::apiResource('patients', PatientController::class);
    Route::get('/patients/{id}/visits', [PatientController::class, 'visits']);
    
    // Dashboard endpoints (protected)
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
    Route::get('/dashboard/payment-stats', [DashboardController::class, 'getPaymentStats']);
    Route::get('/dashboard/top-patients', [DashboardController::class, 'getTopPayingPatients']);
    Route::get('/dashboard/active-patients', [DashboardController::class, 'getActivePatients']);
    
    // Reports endpoints (protected)
    Route::get('/reports/summary', [ReportController::class, 'getSummary']);
    Route::get('/reports/payment-methods', [ReportController::class, 'getPaymentMethods']);
    Route::get('/reports/revenue', [ReportController::class, 'getRevenueAnalytics']);
    Route::get('/reports/invoices', [ReportController::class, 'getInvoiceAnalytics']);
    Route::get('/reports/patients', [ReportController::class, 'getPatientAnalytics']);
    Route::post('/reports/export', [ReportController::class, 'exportReport']);
    Route::get('/reports/batch', [ReportController::class, 'getBatchReports']);
    Route::get('/reports/realtime', [ReportController::class, 'getRealTimeUpdates']);
    Route::post('/reports/search', [ReportController::class, 'searchReports']);
    Route::post('/reports/schedule', [ReportController::class, 'scheduleExport']);
    
    // Download endpoint (public)
    Route::get('/downloads/{fileName}', [ReportController::class, 'downloadExport']);
    Route::get('/dashboard/revenue-summary', [DashboardController::class, 'getRevenueSummary']);
    Route::get('/dashboard/recent-invoices', [DashboardController::class, 'getRecentInvoices']);
    
    // Facility insurance endpoints (protected)
    Route::get('/facilities/{facilityId}/insurances', function ($facilityId) {
        $facility = \App\Models\Facility::findOrFail($facilityId);
        $insurances = $facility->insurances;
        
        return response()->json([
            'data' => \App\Http\Resources\FacilityInsuranceResource::collection($insurances)
        ]);
    });
});

// Public webhook endpoints (no auth - external services)
Route::post('/webhooks/efichepay', [WebhookController::class, 'handleEfichePay'])->name('webhooks.efichepay');
