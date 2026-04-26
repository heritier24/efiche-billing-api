<?php

namespace App\Services;

use App\Models\WebhookEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class WebhookService
{
    public function handleEfichePayWebhook(array $payload): WebhookEvent
    {
        return DB::transaction(function () use ($payload) {
            // Atomic webhook event creation with idempotency
            try {
                $webhookEvent = WebhookEvent::create([
                    'event_id' => $payload['eventId'],
                    'source' => 'efichepay',
                    'event_type' => $payload['status'],
                    'payload' => $payload,
                    'status' => 'received',
                ]);
            } catch (Exception $e) {
                // Handle unique constraint violation (duplicate webhook)
                if ($e->getCode() === '23505' || str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                    return WebhookEvent::where('event_id', $payload['eventId'])->first();
                }
                throw $e;
            }

            // Process the webhook based on event type
            if ($payload['status'] === 'PAYMENT_COMPLETE') {
                app(PaymentService::class)->confirmMobileMoneyPayment($payload, $webhookEvent);
            } elseif ($payload['status'] === 'PAYMENT_FAILED') {
                app(PaymentService::class)->confirmMobileMoneyPayment($payload, $webhookEvent);
            }

            return $webhookEvent;
        });
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, config('services.efichepay.webhook_secret'));
        return hash_equals($expectedSignature, $signature);
    }

    public function isDuplicateEvent(string $eventId): bool
    {
        return WebhookEvent::where('event_id', $eventId)->exists();
    }

    public function getUnprocessedEvents(): \Illuminate\Database\Eloquent\Collection
    {
        return WebhookEvent::where('status', 'received')
            ->orderBy('created_at')
            ->get();
    }

    public function retryFailedEvents(): void
    {
        $failedEvents = WebhookEvent::where('status', 'failed')
            ->orderBy('created_at')
            ->limit(10)
            ->get();

        foreach ($failedEvents as $event) {
            try {
                $this->handleEfichePayWebhook($event->payload);
            } catch (Exception $e) {
                // Log error but continue with next event
                \Log::error('Failed to retry webhook event', [
                    'event_id' => $event->event_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
