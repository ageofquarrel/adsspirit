<?php

namespace Tests\Doubles\Repositories;

use App\DTO\Marketplace\ReceiveWebhookEventDTO;
use App\Models\MarketplaceWebhookEvent;
use App\Repositories\Contracts\MarketplaceWebhookEventRepositoryInterface;
use App\Repositories\MarketplaceWebhookEventRepository;

/**
 * Test double for {@see MarketplaceWebhookEventRepository},
 * which is already covered by its own repository test. Used in service-level
 * tests so they don't have to hit the database again.
 */
final class MarketplaceWebhookEventRepositoryMock implements MarketplaceWebhookEventRepositoryInterface
{
    public bool $existsByEventIdReturn = false;

    public int $createCallCount = 0;

    public ?ReceiveWebhookEventDTO $lastCreatedWith = null;

    /** @var string[] */
    public array $markedProcessed = [];

    public function existsByEventId(string $eventId): bool
    {
        return $this->existsByEventIdReturn;
    }

    public function create(ReceiveWebhookEventDTO $data): MarketplaceWebhookEvent
    {
        $this->createCallCount++;
        $this->lastCreatedWith = $data;

        return new MarketplaceWebhookEvent([
            'event_id' => $data->eventId,
            'type' => $data->type,
            'payload' => $data->payload,
        ]);
    }

    public function markProcessed(string $eventId): void
    {
        $this->markedProcessed[] = $eventId;
    }
}
