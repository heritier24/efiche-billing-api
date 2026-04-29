<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use App\Http\Resources\PaymentResource;
use App\Http\Requests\ProcessPaymentRequest;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            // Find invoice with facility validation
            $invoice = Invoice::with(['visit.patient', 'payments'])
                ->findOrFail($invoiceId);
            
            // Security: Validate user can access this invoice (same facility)
            $userFacilityId = $request->user()->facility_id;
            if ($invoice->visit->facility_id !== $userFacilityId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied',
                    'errors' => [
                        'invoice' => ['You can only process payments for invoices from your facility']
                    ]
                ], 403);
            }
            
            // Validate invoice status
            if (!in_array($invoice->status, ['pending', 'partially_paid'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot process payment for this invoice',
                    'errors' => [
                        'invoice' => ['Invoice must be pending or partially paid to process payments'],
                        'current_status' => [$invoice->status]
                    ]
                ], 409);
            }
            
            // Calculate remaining balance
            $totalPaid = $invoice->payments->where('status', 'confirmed')->sum('amount');
            $remainingBalance = $invoice->total_amount - $totalPaid;
            
            // Additional validation for payment amount
            $paymentAmount = (float) $request->amount;
            if ($paymentAmount > $remainingBalance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount exceeds remaining balance',
                    'errors' => [
                        'amount' => [
                            "Payment amount (RWF {$paymentAmount}) exceeds remaining balance (RWF {$remainingBalance})"
                        ],
                        'remaining_balance' => [$remainingBalance]
                    ]
                ], 422);
            }
            
            // Process payment with user context
            $payment = $this->paymentService->processPayment($invoice, $request);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'data' => new PaymentResource($payment),
                'invoice_status' => $invoice->fresh()->status,
                'remaining_balance' => max(0, $remainingBalance - $paymentAmount)
            ], 201);
            
        } catch (Exception $e) {
            $message = $e->getMessage();
            
            // Handle specific validation errors
            if (str_contains($message, 'exceeds remaining balance')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount exceeds remaining balance',
                    'errors' => [
                        'amount' => [$message]
                    ]
                ], 422);
            }
            
            if (str_contains($message, 'ModelNotFoundException') || str_contains($message, 'No query results')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found',
                    'errors' => [
                        'invoice' => ['The specified invoice does not exist']
                    ]
                ], 404);
            }
            
            // Handle generic errors
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment',
                'errors' => [
                    'general' => [$message]
                ]
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

    public function getPaymentSummary(Request $request): JsonResponse
    {
        try {
            // Validate query parameters
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid query parameters',
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            
            $summary = $this->paymentService->getPaymentSummary($dateFrom, $dateTo);
            
            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve payment summary',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getRevenueTrends(Request $request): JsonResponse
    {
        try {
            // Validate query parameters
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'group_by' => 'nullable|in:daily,weekly,monthly'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid query parameters',
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $groupBy = $request->input('group_by', 'daily');
            $userFacilityId = auth()->user()?->facility_id;
            
            $trends = $this->paymentService->getRevenueTrends($dateFrom, $dateTo, $userFacilityId, $groupBy);
            
            return response()->json([
                'success' => true,
                'data' => $trends
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve revenue trends',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            // Validate query parameters
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'search' => 'nullable|string|max:255',
                'status' => 'nullable|in:pending,confirmed,failed',
                'method' => 'nullable|in:cash,mobile_money,insurance',
                'page' => 'nullable|integer|min:1',
                'limit' => 'nullable|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid query parameters',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();
            
            // Set default values
            $page = $validated['page'] ?? 1;
            $limit = $validated['limit'] ?? 20;
            $search = $validated['search'] ?? null;
            $status = $validated['status'] ?? null;
            $method = $validated['method'] ?? null;

            // Get payments with filtering and pagination
            $result = $this->paymentService->getPaymentsWithFilters(
                search: $search,
                status: $status,
                method: $method,
                page: $page,
                limit: $limit,
                userFacilityId: $request->user()->facility_id
            );

            return response()->json([
                'success' => true,
                'data' => PaymentResource::collection($result['payments']),
                'total' => $result['total'],
                'per_page' => $limit,
                'current_page' => $page,
                'last_page' => ceil($result['total'] / $limit)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve payments',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, int $paymentId): JsonResponse
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'status' => 'required|in:pending,confirmed,failed'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status',
                    'errors' => $validator->errors()
                ], 422);
            }

            $payment = $this->paymentService->updatePaymentStatus($paymentId, $request->status);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment status updated successfully',
                'data' => new PaymentResource($payment)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update payment status',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(int $paymentId): JsonResponse
    {
        try {
            $this->paymentService->deletePayment($paymentId);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete payment',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function retryPayment(int $paymentId): JsonResponse
    {
        try {
            $payment = $this->paymentService->retryPayment($paymentId);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment retry initiated',
                'data' => new PaymentResource($payment)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retry payment',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
