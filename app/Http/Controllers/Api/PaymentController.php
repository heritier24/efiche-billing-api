<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use App\Http\Resources\PaymentResource;
use App\Http\Requests\ProcessPaymentRequest;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Exception;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function processPayment(ProcessPaymentRequest $request, int $invoiceId): JsonResponse
    {
        try {
            $invoice = Invoice::findOrFail($invoiceId);
            $payment = $this->paymentService->processPayment($invoice, $request);
            
            return response()->json(new PaymentResource($payment), 201);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to process payment',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getPaymentStatus(int $paymentId): JsonResponse
    {
        try {
            $payment = $this->paymentService->getPaymentStatus($paymentId);
            
            if (!$payment) {
                return response()->json([
                    'error' => 'Payment not found',
                    'message' => 'Payment with ID: ' . $paymentId . ' does not exist'
                ], 404);
            }

            return response()->json([
                'id' => $payment->id,
                'status' => $payment->status,
                'confirmed_at' => $payment->confirmed_at?->toISOString(),
                'transaction_ref' => $payment->transaction_ref
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve payment status',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function index(): JsonResponse
    {
        try {
            $payments = $this->paymentService->getAllPayments();
            return response()->json(PaymentResource::collection($payments));
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve payments',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $payment = $this->paymentService->getPaymentById($id);
            
            if (!$payment) {
                return response()->json([
                    'error' => 'Payment not found',
                    'message' => 'Payment with ID: ' . $id . ' does not exist'
                ], 404);
            }

            return response()->json(new PaymentResource($payment));
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve payment',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
