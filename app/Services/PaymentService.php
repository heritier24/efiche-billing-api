<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Http\Requests\ProcessPaymentRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Exception;

class PaymentService
{
    public function processPayment(Invoice $invoice, ProcessPaymentRequest $request): Payment
    {
        return DB::transaction(function () use ($invoice, $request) {
            // CRITICAL: Lock the invoice row for concurrency protection
            $lockedInvoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            
            // Calculate remaining balance safely
            $totalPaid = Payment::where('invoice_id', $invoice->id)
                               ->where('status', 'confirmed')
                               ->sum('amount');
            
            $remainingBalance = $lockedInvoice->patient_responsibility - $totalPaid;

            if ($request->amount > $remainingBalance) {
                throw new Exception('Payment amount exceeds remaining balance');
            }

            $paymentData = [
                'invoice_id' => $invoice->id,
                'payment_method' => $request->method,
                'amount' => $request->amount,
                'status' => 'pending',
                'notes' => $request->notes,
                'cashier_id' => auth()->id(),
            ];

            if ($request->method === 'cash') {
                $paymentData['status'] = 'confirmed';
                $paymentData['confirmed_at'] = now();
            } elseif ($request->method === 'mobile_money') {
                $paymentData['transaction_ref'] = $this->initiateMobileMoneyPayment([
                    'amount' => $request->amount,
                    'phone_number' => $request->phone_number,
                    'invoice_id' => $invoice->id,
                ]);
            }

            $payment = Payment::create($paymentData);

            if ($payment->status === 'confirmed') {
                $lockedInvoice->updateStatus();
            }

            return $payment;
        });
    }

    public function getPaymentStatus(int $paymentId): ?Payment
    {
        return Payment::with('invoice')->find($paymentId);
    }

    public function confirmMobileMoneyPayment(array $payload, WebhookEvent $webhookEvent): void
    {
        DB::transaction(function () use ($payload, $webhookEvent) {
            $payment = Payment::where('transaction_ref', $payload['orderNumber'])
                              ->where('status', 'pending')
                              ->first();

            if (!$payment) {
                throw new Exception('Payment not found or already processed');
            }

            if ($payload['status'] === 'PAYMENT_COMPLETE') {
                $payment->confirm();
                $payment->webhook_event_id = $webhookEvent->id;
                $payment->save();
            } elseif ($payload['status'] === 'PAYMENT_FAILED') {
                $payment->status = 'failed';
                $payment->webhook_event_id = $webhookEvent->id;
                $payment->save();
            }

            $webhookEvent->markAsProcessed();
        });
    }

    private function initiateMobileMoneyPayment(array $data): string
    {
        $response = Http::post('https://api.efichepay.rw/payments', [
            'amount' => $data['amount'] * 100, // Convert to cents
            'phone' => $data['phone_number'],
            'merchant_id' => config('services.efichepay.merchant_id'),
            'callback_url' => route('webhooks.efichepay'),
            'order_number' => 'PAY-' . uniqid(),
        ]);

        if (!$response->successful()) {
            throw new Exception('Mobile money payment initiation failed: ' . $response->body());
        }

        return $response->json()['transaction_ref'];
    }

    public function validatePaymentAmount(Invoice $invoice, float $amount): bool
    {
        $totalPaid = $invoice->confirmedPayments()->sum('amount');
        $remainingBalance = $invoice->patient_responsibility - $totalPaid;
        
        return $amount <= $remainingBalance && $amount > 0;
    }

    public function getAllPayments()
    {
        return Payment::with('invoice')->get();
    }

    public function getPaymentById(int $id): ?Payment
    {
        return Payment::with('invoice')->find($id);
    }
}
