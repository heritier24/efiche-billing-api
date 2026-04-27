<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function getInvoiceByVisit(int $visitId): ?Invoice
    {
        return Invoice::with(['lineItems', 'visit.patient', 'visit.facility'])
            ->whereHas('visit', function ($query) use ($visitId) {
                $query->where('id', $visitId);
            })
            ->first();
    }

    public function createInvoiceForVisit(int $visitId, array $data): Invoice
    {
        return DB::transaction(function () use ($visitId, $data) {
            $visit = Visit::findOrFail($visitId);
            $lineItems = $data['line_items'];
            
            $totalAmount = collect($lineItems)->sum(function ($item) {
                return $item['quantity'] * $item['unit_price'];
            });

            $invoice = Invoice::create([
                'visit_id' => $visit->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'insurance_coverage' => $this->calculateInsuranceCoverage($visit->facility_id, $totalAmount),
                'due_date' => now()->addDays(30),
            ]);

            foreach ($lineItems as $item) {
                InvoiceLineItem::create([
                    'invoice_id' => $invoice->id,
                    'item_code' => $item['item_code'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);
            }

            return $invoice;
        });
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . date('Y');
        $latestInvoice = Invoice::where('invoice_number', 'like', $prefix . '%')
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($latestInvoice) {
            $lastNumber = (int) substr($latestInvoice->invoice_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    private function calculateInsuranceCoverage(int $facilityId, float $totalAmount): float
    {
        // For now, return 0. In a real implementation, this would:
        // 1. Get patient's insurance from the visit
        // 2. Check facility insurance coverage percentage
        // 3. Apply coverage rules and limits
        
        return 0.00;
    }

    public function updateInvoiceStatus(Invoice $invoice): void
    {
        $invoice->updateStatus();
    }

    public function getAllInvoices()
    {
        return Invoice::with(['lineItems', 'visit.patient', 'visit.facility'])->get();
    }

    public function getInvoiceById(int $id): ?Invoice
    {
        return Invoice::with(['lineItems', 'visit.patient', 'visit.facility'])->find($id);
    }

    public function createInvoice(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $lineItems = $data['line_items'];
            
            $totalAmount = collect($lineItems)->sum(function ($item) {
                return $item['quantity'] * $item['unit_price'];
            });

            $visit = Visit::findOrFail($data['visit_id']);

            $invoice = Invoice::create([
                'visit_id' => $data['visit_id'],
                'invoice_number' => $this->generateInvoiceNumber(),
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'insurance_coverage' => $this->calculateInsuranceCoverage($visit->facility_id, $totalAmount),
                'due_date' => isset($data['due_date']) ? $data['due_date'] : now()->addDays(30),
            ]);

            foreach ($lineItems as $item) {
                InvoiceLineItem::create([
                    'invoice_id' => $invoice->id,
                    'item_code' => $item['item_code'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);
            }

            return $invoice;
        });
    }

    public function getInvoicesWithFilters(?string $status = null, int $limit = 50, int $page = 1, ?string $search = null, ?int $userFacilityId = null): array
    {
        $query = Invoice::with(['visit.patient', 'lineItems'])
            ->when($userFacilityId, function ($q, $facilityId) {
                // Security: Only show invoices from user's facility
                return $q->whereHas('visit', function ($subQuery) use ($facilityId) {
                    $subQuery->where('facility_id', $facilityId);
                });
            })
            ->when($status, function ($q, $status) {
                return $q->where('status', $status);
            })
            ->when($search, function ($q, $search) {
                return $q->where(function ($subQuery) use ($search) {
                    $subQuery->where('invoice_number', 'LIKE', "%{$search}%")
                            ->orWhereHas('visit.patient', function ($patientQuery) use ($search) {
                                $patientQuery->where('first_name', 'LIKE', "%{$search}%")
                                           ->orWhere('last_name', 'LIKE', "%{$search}%");
                            });
                });
            });

        // Get total count for pagination
        $total = $query->count();

        // Get paginated results
        $invoices = $query->orderBy('created_at', 'desc')
                          ->offset(($page - 1) * $limit)
                          ->limit($limit)
                          ->get();

        return [
            'invoices' => $invoices,
            'total' => $total,
        ];
    }

    public function getPendingInvoices(int $userFacilityId): array
    {
        $invoices = Invoice::with(['visit.patient', 'payments'])
            ->whereHas('visit', function ($query) use ($userFacilityId) {
                $query->where('facility_id', $userFacilityId);
            })
            ->whereIn('status', ['pending', 'partially_paid'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($invoice) {
                $totalPaid = $invoice->payments->where('status', 'confirmed')->sum('amount');
                $remainingBalance = $invoice->total_amount - $totalPaid;
                
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'patient_name' => $invoice->visit->patient->full_name,
                    'total_amount' => (float) $invoice->total_amount,
                    'remaining_balance' => (float) $remainingBalance,
                    'created_at' => $invoice->created_at->toISOString(),
                    'status' => $invoice->status,
                ];
            })
            ->toArray();

        return $invoices;
    }
}
