<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Marketplace\MapReceiveWebhookEventAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\StoreMarketplaceWebhookRequest;
use App\Services\Marketplace\ApplyOrderEventService;
use App\Services\Marketplace\ReceiveWebhookEventService;
use Illuminate\Http\JsonResponse;

final class MarketplaceWebhookController extends Controller
{
    public function store(
        StoreMarketplaceWebhookRequest $request,
        ReceiveWebhookEventService $receiveService,
        ApplyOrderEventService $applyService,
        MapReceiveWebhookEventAction $mapEvent,
    ): JsonResponse {
        $event = $mapEvent->execute($request->json()->all());

        $wasDuplicate = $receiveService->handle($event);

        if (! $wasDuplicate) {
            $applyService->handle($event->payload->order);
            $receiveService->markProcessed($event->eventId);
        }

        return response()->json(['status' => 'ok']);
    }
}
