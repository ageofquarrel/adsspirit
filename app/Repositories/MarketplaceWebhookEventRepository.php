<?php

namespace App\Repositories;

use App\DTO\Marketplace\ReceiveWebhookEventDTO;
use App\Models\MarketplaceWebhookEvent;
use App\Repositories\Contracts\MarketplaceWebhookEventRepositoryInterface;

final class MarketplaceWebhookEventRepository implements MarketplaceWebhookEventRepositoryInterface
{
    public function findByEventId(string $eventId): ?MarketplaceWebhookEvent
    {
        return MarketplaceWebhookEvent::query()
            ->where('event_id', $eventId)
            ->first();
    }

    public function create(ReceiveWebhookEventDTO $data): MarketplaceWebhookEvent
    {
        return MarketplaceWebhookEvent::query()->create([
            'event_id' => $data->eventId,
            'type' => $data->type,
            'payload' => $data->payload,
        ]);
    }

    public function markProcessed(string $eventId): void
    {
        MarketplaceWebhookEvent::query()
            ->where('event_id', $eventId)
            ->update(['processed_at' => now()]);
    }
}
