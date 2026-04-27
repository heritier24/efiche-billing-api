<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InvoiceService;
use App\Http\Resources\InvoiceResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class InvoiceController extends Controller
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function createInvoiceForVisit(Request $request, int $visitId): JsonResponse
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'line_items' => 'required|array|min:1',
                'line_items.*.item_code' => 'required|string|max:255',
                'line_items.*.description' => 'required|string|max:255',
                'line_items.*.quantity' => 'required|integer|min:1',
                'line_items.*.unit_price' => 'required|numeric|min:0',
                'insurance_id' => 'nullable|exists:insurances,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $invoice = $this->invoiceService->createInvoiceForVisit($visitId, $validator->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully',
                'data' => new InvoiceResource($invoice)
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create invoice',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getInvoiceByVisit(int $visitId): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->getInvoiceByVisit($visitId);
            
            if (!$invoice) {
                return response()->json([
                    'error' => 'Invoice not found for this visit',
                    'message' => 'No invoice exists for visit ID: ' . $visitId
                ], 404);
            }

            return response()->json(new InvoiceResource($invoice));
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve invoice',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            // Validate query parameters
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'status' => 'nullable|in:pending,partially_paid,paid,overdue,cancelled',
                'limit' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
                'search' => 'nullable|string|max:255',
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
            $limit = $validated['limit'] ?? 50;
            $page = $validated['page'] ?? 1;
            $status = $validated['status'] ?? null;
            $search = $validated['search'] ?? null;

            // Get invoices with filtering and pagination
            $result = $this->invoiceService->getInvoicesWithFilters(
                status: $status,
                limit: $limit,
                page: $page,
                search: $search,
                userFacilityId: $request->user()->facility_id
            );

            return response()->json([
                'success' => true,
                'data' => InvoiceResource::collection($result['invoices']),
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit,
                'last_page' => ceil($result['total'] / $limit)
            ]);
        } catch (Exception $e) {
            Log::error('Failed to retrieve invoices:', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve invoices',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            // Debug: Log incoming request data
            Log::info('Invoice creation request:', [
                'all_data' => $request->all(),
                'visit_id' => $request->input('visit_id'),
                'line_items' => $request->input('line_items')
            ]);

            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'visit_id' => 'required|exists:visits,id',
                'line_items' => 'required|array|min:1',
                'line_items.*.item_code' => 'required|string|max:50',
                'line_items.*.description' => 'required|string',
                'line_items.*.quantity' => 'required|integer|min:1',
                'line_items.*.unit_price' => 'required|numeric|min:0',
                'insurance_id' => 'nullable|exists:insurances,id',
                'due_date' => 'nullable|date|after:today'
            ]);

            if ($validator->fails()) {
                // Debug: Log validation errors
                Log::error('Invoice validation failed:', [
                    'errors' => $validator->errors()->toArray(),
                    'request_data' => $request->all()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $invoice = $this->invoiceService->createInvoice($validator->validated());
            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully',
                'data' => new InvoiceResource($invoice)
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create invoice',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->getInvoiceById($id);
            
            if (!$invoice) {
                return response()->json([
                    'error' => 'Invoice not found',
                    'message' => 'Invoice with ID: ' . $id . ' does not exist'
                ], 404);
            }

            return response()->json(new InvoiceResource($invoice));
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve invoice',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getPendingInvoices(Request $request): JsonResponse
    {
        try {
            $invoices = $this->invoiceService->getPendingInvoices($request->user()->facility_id);
            
            return response()->json([
                'success' => true,
                'data' => $invoices
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve pending invoices',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
