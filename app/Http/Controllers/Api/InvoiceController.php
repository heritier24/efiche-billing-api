<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InvoiceService;
use App\Http\Resources\InvoiceResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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

            $invoice = $this->invoiceService->createInvoiceForVisit($visitId, $request->validated());
            
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

    public function index(): JsonResponse
    {
        try {
            $invoices = $this->invoiceService->getAllInvoices();
            return response()->json(InvoiceResource::collection($invoices));
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve invoices',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->createInvoice($request->validated());
            return response()->json(new InvoiceResource($invoice), 201);
        } catch (Exception $e) {
            return response()->json([
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
}
