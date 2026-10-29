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
     * Persists the event the first time it's seen. If it was already stored
     * by an earlier delivery attempt but never finished processing (e.g. the
     * worker crashed between storing it and applying it to the order), this
     * does *not* skip it — only a fully processed event is treated as a
     * no-op duplicate, so a MarketHub retry can still complete the work.
     *
     * @return bool `true` when the event was already fully processed before.
     */
    public function handle(ReceiveWebhookEventDTO $data): bool
    {
        $existing = $this->repository->findByEventId($data->eventId);

        if ($existing === null) {
            $this->repository->create($data);

            return false;
        }

        return $existing->processed_at !== null;
    }

    public function markProcessed(string $eventId): void
    {
        $this->repository->markProcessed($eventId);
    }
}
