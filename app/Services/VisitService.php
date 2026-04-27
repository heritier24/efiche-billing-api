<?php

namespace App\Services;

use App\Models\Visit;
use App\Models\Patient;
use App\Models\Facility;
use Illuminate\Support\Facades\DB;
use Exception;

class VisitService
{
    /**
     * Get visits with filtering and pagination
     */
    public function getVisitsWithFilters(?int $patientId = null, ?string $status = null, ?string $visitType = null, int $page = 1, int $limit = 50, ?int $userFacilityId = null): array
    {
        $query = Visit::with(['patient'])
            ->when($userFacilityId, function ($q, $facilityId) {
                // Security: Only show visits from user's facility
                return $q->where('facility_id', $facilityId);
            })
            ->when($patientId, function ($q, $patientId) {
                return $q->where('patient_id', $patientId);
            })
            ->when($status, function ($q, $status) {
                return $q->where('status', $status);
            })
            ->when($visitType, function ($q, $visitType) {
                return $q->where('visit_type', $visitType);
            });

        // Get total count for pagination
        $total = $query->count();

        // Get paginated results
        $visits = $query->orderBy('created_at', 'desc')
                        ->offset(($page - 1) * $limit)
                        ->limit($limit)
                        ->get();

        return [
            'visits' => $visits,
            'total' => $total,
        ];
    }

    /**
     * Create a new visit
     */
    public function createVisit(array $data, int $userFacilityId): Visit
    {
        return DB::transaction(function () use ($data, $userFacilityId) {
            // Validate patient exists and is accessible
            $patient = Patient::findOrFail($data['patient_id']);
            
            // Validate facility access
            $facilityId = $data['facility_id'] ?? $userFacilityId;
            if ($facilityId !== $userFacilityId) {
                throw new Exception('You can only create visits for your facility');
            }

            // Create visit
            $visit = Visit::create([
                'patient_id' => $data['patient_id'],
                'facility_id' => $facilityId,
                'visit_type' => $data['visit_type'],
                'status' => $data['status'] ?? 'active',
                'notes' => $data['notes'] ?? null,
            ]);

            return $visit;
        });
    }

    /**
     * Get visit details with patient and invoice information
     */
    public function getVisitById(int $id, ?int $userFacilityId = null): ?Visit
    {
        $query = Visit::with(['patient', 'invoices']);

        if ($userFacilityId) {
            $query->where('facility_id', $userFacilityId);
        }

        return $query->find($id);
    }

    /**
     * Update visit status
     */
    public function updateVisitStatus(int $visitId, string $status, ?int $userFacilityId = null): Visit
    {
        return DB::transaction(function () use ($visitId, $status, $userFacilityId) {
            $visit = Visit::lockForUpdate()->findOrFail($visitId);

            // Security: Check facility access
            if ($userFacilityId && $visit->facility_id !== $userFacilityId) {
                throw new Exception('Access denied: You can only update visits from your facility');
            }

            // Validate status transition
            $validStatuses = ['active', 'completed', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                throw new Exception('Invalid status. Must be one of: ' . implode(', ', $validStatuses));
            }

            // Business logic: Cannot delete visits with associated invoices
            if ($status === 'cancelled' && $visit->invoices()->exists()) {
                throw new Exception('Cannot cancel visits with associated invoices');
            }

            $visit->status = $status;
            $visit->save();

            return $visit;
        });
    }

    /**
     * Get all visits (for admin purposes)
     */
    public function getAllVisits()
    {
        return Visit::with(['patient', 'invoices'])->get();
    }

    /**
     * Get visit by ID (basic version)
     */
    public function getVisit(int $id): ?Visit
    {
        return Visit::with(['patient', 'invoices'])->find($id);
    }

    /**
     * Validate visit data
     */
    public function validateVisitData(array $data): array
    {
        $errors = [];

        // Patient validation
        if (!isset($data['patient_id']) || !is_numeric($data['patient_id'])) {
            $errors['patient_id'] = ['Patient ID is required and must be a valid integer'];
        } else {
            $patient = Patient::find($data['patient_id']);
            if (!$patient) {
                $errors['patient_id'] = ['Patient not found'];
            }
        }

        // Visit type validation
        $validVisitTypes = ['consultation', 'follow_up', 'emergency', 'general'];
        if (!isset($data['visit_type']) || !in_array($data['visit_type'], $validVisitTypes)) {
            $errors['visit_type'] = ['Visit type is required and must be one of: ' . implode(', ', $validVisitTypes)];
        }

        // Status validation (optional)
        if (isset($data['status'])) {
            $validStatuses = ['active', 'completed', 'cancelled'];
            if (!in_array($data['status'], $validStatuses)) {
                $errors['status'] = ['Status must be one of: ' . implode(', ', $validStatuses)];
            }
        }

        // Notes validation (optional)
        if (isset($data['notes']) && strlen($data['notes']) > 1000) {
            $errors['notes'] = ['Notes cannot exceed 1000 characters'];
        }

        return $errors;
    }

    /**
     * Get visit statistics for a facility
     */
    public function getVisitStatistics(int $facilityId): array
    {
        $visits = Visit::where('facility_id', $facilityId);

        return [
            'total_visits' => $visits->count(),
            'active_visits' => $visits->where('status', 'active')->count(),
            'completed_visits' => $visits->where('status', 'completed')->count(),
            'cancelled_visits' => $visits->where('status', 'cancelled')->count(),
            'visit_types' => [
                'consultation' => $visits->where('visit_type', 'consultation')->count(),
                'follow_up' => $visits->where('visit_type', 'follow_up')->count(),
                'emergency' => $visits->where('visit_type', 'emergency')->count(),
                'general' => $visits->where('visit_type', 'general')->count(),
            ],
            'today_visits' => $visits->whereDate('created_at', today())->count(),
        ];
    }
}
