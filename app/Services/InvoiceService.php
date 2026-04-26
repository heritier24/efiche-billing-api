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

    public function createInvoiceForVisit(Visit $visit, array $lineItems): Invoice
    {
        return DB::transaction(function () use ($visit, $lineItems) {
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
        // This would be implemented based on the frontend requirements
        // For now, return a basic implementation
        return Invoice::create($data);
    }
}
