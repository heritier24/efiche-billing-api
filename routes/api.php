<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\DashboardController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Invoice endpoints
Route::get('/visits/{visitId}/invoice', [InvoiceController::class, 'getInvoiceByVisit']);
Route::apiResource('invoices', InvoiceController::class);

// Payment endpoints
Route::post('/invoices/{invoiceId}/payments', [PaymentController::class, 'processPayment']);
Route::get('/payments/{paymentId}/status', [PaymentController::class, 'getPaymentStatus']);
Route::apiResource('payments', PaymentController::class);

// Webhook endpoints
Route::post('/webhooks/efichepay', [WebhookController::class, 'handleEfichePay']);

// Dashboard endpoints
Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
Route::get('/dashboard/payment-stats', [DashboardController::class, 'getPaymentStats']);
Route::get('/dashboard/top-patients', [DashboardController::class, 'getTopPayingPatients']);

// Facility insurance endpoints
Route::get('/facilities/{facilityId}/insurances', function ($facilityId) {
    $facility = \App\Models\Facility::findOrFail($facilityId);
    $insurances = $facility->insurances;
    
    return response()->json([
        'data' => \App\Http\Resources\FacilityInsuranceResource::collection($insurances)
    ]);
});
