<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VisitService;
use App\Http\Resources\VisitResource;
use App\Http\Requests\VisitRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class VisitController extends Controller
{
    protected VisitService $visitService;

    public function __construct(VisitService $visitService)
    {
        $this->visitService = $visitService;
    }

    /**
     * Get visits with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validate query parameters
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'patient_id' => 'nullable|integer|exists:patients,id',
                'status' => 'nullable|in:active,completed,cancelled',
                'visit_type' => 'nullable|in:consultation,follow_up,emergency,general',
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
            $limit = $validated['limit'] ?? 50;
            $patientId = $validated['patient_id'] ?? null;
            $status = $validated['status'] ?? null;
            $visitType = $validated['visit_type'] ?? null;

            // Get visits with filtering and pagination
            $result = $this->visitService->getVisitsWithFilters(
                patientId: $patientId,
                status: $status,
                visitType: $visitType,
                page: $page,
                limit: $limit,
                userFacilityId: $request->user()->facility_id
            );

            return response()->json([
                'success' => true,
                'data' => VisitResource::collection($result['visits']),
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve visits',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new visit
     */
    public function store(VisitRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            // Create visit with user's facility as default
            $visit = $this->visitService->createVisit($validated, $request->user()->facility_id);
            
            return response()->json([
                'success' => true,
                'message' => 'Visit created successfully',
                'data' => new VisitResource($visit)
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create visit',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get visit details with patient and invoice information
     */
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $visit = $this->visitService->getVisitById($id, $request->user()->facility_id);
            
            if (!$visit) {
                return response()->json([
                    'success' => false,
                    'error' => 'Visit not found',
                    'message' => 'Visit with ID: ' . $id . ' does not exist'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new VisitResource($visit)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve visit',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update visit status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'status' => 'required|in:active,completed,cancelled'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status',
                    'errors' => $validator->errors()
                ], 422);
            }

            $visit = $this->visitService->updateVisitStatus($id, $request->status, $request->user()->facility_id);
            
            return response()->json([
                'success' => true,
                'message' => 'Visit status updated successfully',
                'data' => new VisitResource($visit)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update visit status',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get visit statistics (for dashboard)
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $statistics = $this->visitService->getVisitStatistics($request->user()->facility_id);
            
            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve visit statistics',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
