<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Exception;

class PatientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Patient::with(['insurance', 'visits']);
            
            // Search functionality
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }
            
            // Status filter
            if ($request->has('status')) {
                $query->where('status', $request->get('status'));
            }
            
            // Pagination
            $perPage = $request->get('limit', 20);
            $patients = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            // Calculate additional fields
            $patients->getCollection()->transform(function ($patient) {
                $patient->total_visits = $patient->visits->count();
                $patient->total_billed = $patient->visits->sum(function ($visit) {
                    return $visit->invoice ? $visit->invoice->total_amount : 0;
                });
                $patient->total_paid = $patient->visits->sum(function ($visit) {
                    return $visit->invoice ? $visit->invoice->total_paid : 0;
                });
                $patient->outstanding_balance = $patient->total_billed - $patient->total_paid;
                $patient->last_visit_date = $patient->visits->max('created_at');
                $patient->insurance_name = $patient->insurance ? $patient->insurance->name : null;
                
                return $patient;
            });
            
            return response()->json([
                'data' => $patients->items(),
                'total' => $patients->total(),
                'per_page' => $patients->perPage(),
                'current_page' => $patients->currentPage(),
                'last_page' => $patients->lastPage()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve patients',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:patients,email',
                'phone' => 'required|string|regex:/^\+2507\d{8}$/',
                'date_of_birth' => 'required|date|before:today',
                'gender' => 'required|in:male,female,other',
                'address' => 'required|string',
                'insurance_id' => 'nullable|exists:insurances,id'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $patient = Patient::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'address' => $request->address,
                'insurance_id' => $request->insurance_id,
                'registration_date' => now()->toDateString(),
                'status' => 'active'
            ]);
            
            $patient->load('insurance');
            $patient->insurance_name = $patient->insurance ? $patient->insurance->name : null;
            
            return response()->json([
                'success' => true,
                'message' => 'Patient created successfully',
                'data' => $patient
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create patient',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function show(int $id): JsonResponse
    {
        try {
            $patient = Patient::with(['insurance', 'visits.invoice.payments'])
                ->findOrFail($id);
            
            // Calculate additional fields
            $patient->total_visits = $patient->visits->count();
            $patient->total_billed = $patient->visits->sum(function ($visit) {
                return $visit->invoice ? $visit->invoice->total_amount : 0;
            });
            $patient->total_paid = $patient->visits->sum(function ($visit) {
                return $visit->invoice ? $visit->invoice->total_paid : 0;
            });
            $patient->outstanding_balance = $patient->total_billed - $patient->total_paid;
            $patient->last_visit_date = $patient->visits->max('created_at');
            $patient->insurance_name = $patient->insurance ? $patient->insurance->name : null;
            
            // Format visits for response
            $patient->visits = $patient->visits->map(function ($visit) {
                return [
                    'id' => $visit->id,
                    'visit_date' => $visit->created_at->toISOString(),
                    'visit_type' => $visit->visit_type,
                    'status' => $visit->status,
                    'invoice_id' => $visit->invoice ? $visit->invoice->id : null,
                    'invoice_number' => $visit->invoice ? $visit->invoice->invoice_number : null,
                    'total_amount' => $visit->invoice ? $visit->invoice->total_amount : 0,
                    'paid_amount' => $visit->invoice ? $visit->invoice->total_paid : 0
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $patient
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve patient',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $patient = Patient::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'first_name' => 'sometimes|required|string|max:255',
                'last_name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|nullable|email|unique:patients,email,' . $id,
                'phone' => 'sometimes|required|string|regex:/^\+2507\d{8}$/',
                'address' => 'sometimes|required|string',
                'insurance_id' => 'sometimes|nullable|exists:insurances,id'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $patient->update($request->only([
                'first_name', 'last_name', 'email', 'phone', 'address', 'insurance_id'
            ]));
            
            $patient->load('insurance');
            $patient->insurance_name = $patient->insurance ? $patient->insurance->name : null;
            
            return response()->json([
                'success' => true,
                'message' => 'Patient updated successfully',
                'data' => $patient
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update patient',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function visits(int $id): JsonResponse
    {
        try {
            $patient = Patient::findOrFail($id);
            
            $visits = Visit::with(['invoice.payments'])
                ->where('patient_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();
            
            $visitData = $visits->map(function ($visit) {
                return [
                    'id' => $visit->id,
                    'visit_date' => $visit->created_at->toISOString(),
                    'visit_type' => $visit->visit_type,
                    'status' => $visit->status,
                    'invoice_id' => $visit->invoice ? $visit->invoice->id : null,
                    'invoice_number' => $visit->invoice ? $visit->invoice->invoice_number : null,
                    'total_amount' => $visit->invoice ? $visit->invoice->total_amount : 0,
                    'paid_amount' => $visit->invoice ? $visit->invoice->total_paid : 0
                ];
            });
            
            return response()->json([
                'data' => $visitData,
                'total' => $visits->count(),
                'last_visit' => $visits->max('created_at')
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve patient visits',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
