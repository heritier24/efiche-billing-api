<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WebhookService;
use App\Http\Requests\WebhookRequest;
use Illuminate\Http\JsonResponse;
use Exception;

class WebhookController extends Controller
{
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    public function handleEfichePay(WebhookRequest $request): JsonResponse
    {
        try {
            $webhookEvent = $this->webhookService->handleEfichePayWebhook($request->validated());
            
            return response()->json([
                'status' => 'ok',
                'event_id' => $webhookEvent->event_id,
                'processed' => $webhookEvent->status === 'processed'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
