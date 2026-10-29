<?php

namespace App\Services\Marketplace;

use App\DTO\Marketplace\ReceiveWebhookEventDTO;
use App\Repositories\Contracts\MarketplaceWebhookEventRepositoryInterface;

final readonly class ReceiveWebhookEventService
{
    public function __construct(
        private MarketplaceWebhookEventRepositoryInterface $repository,
    ) {}

    /**
     * Persists the event unless it has already been received before.
     *
     * @return bool `true` when the event is a duplicate (already stored earlier).
     */
    public function handle(ReceiveWebhookEventDTO $data): bool
    {
        if ($this->repository->existsByEventId($data->eventId)) {
            return true;
        }

        $this->repository->create($data);

        return false;
    }

    public function markProcessed(string $eventId): void
    {
        $this->repository->markProcessed($eventId);
    }
}
