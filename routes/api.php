<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PatientController;

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
    Route::get('/visits/{visitId}/invoice', [InvoiceController::class, 'getInvoiceByVisit']);
    Route::apiResource('invoices', InvoiceController::class);
    
    // Payment endpoints (protected)
    Route::post('/invoices/{invoiceId}/payments', [PaymentController::class, 'processPayment']);
    Route::get('/payments/{paymentId}/status', [PaymentController::class, 'getPaymentStatus']);
    Route::apiResource('payments', PaymentController::class);
    
    // Patient endpoints (protected)
    Route::apiResource('patients', PatientController::class);
    Route::get('/patients/{id}/visits', [PatientController::class, 'visits']);
    
    // Dashboard endpoints (protected)
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
    Route::get('/dashboard/payment-stats', [DashboardController::class, 'getPaymentStats']);
    Route::get('/dashboard/top-patients', [DashboardController::class, 'getTopPayingPatients']);
    
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
Route::post('/webhooks/efichepay', [WebhookController::class, 'handleEfichePay']);
