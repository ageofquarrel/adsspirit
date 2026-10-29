<?php

namespace App\Http\Controllers\Api;

use App\DTO\Marketplace\ReceiveWebhookEventDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\StoreMarketplaceWebhookRequest;
use App\Services\Marketplace\ApplyOrderEventService;
use App\Services\Marketplace\ReceiveWebhookEventService;
use Illuminate\Http\JsonResponse;

final class MarketplaceWebhookController extends Controller
{
    public function __invoke(
        StoreMarketplaceWebhookRequest $request,
        ReceiveWebhookEventService $receiveService,
        ApplyOrderEventService $applyService,
    ): JsonResponse {
        $payload = $request->json()->all();
        $eventId = $request->validated('id');

        $wasDuplicate = $receiveService->handle(new ReceiveWebhookEventDTO(
            eventId: $eventId,
            type: $request->validated('type'),
            payload: $payload,
        ));

        if (! $wasDuplicate) {
            $applyService->handle($payload['data']['order']);
            $receiveService->markProcessed($eventId);
        }

        return response()->json(['status' => 'ok']);
    }
}
