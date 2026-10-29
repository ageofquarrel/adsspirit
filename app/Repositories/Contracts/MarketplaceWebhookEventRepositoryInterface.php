<?php

namespace App\Repositories\Contracts;

use App\DTO\Marketplace\ReceiveWebhookEventDTO;
use App\Models\MarketplaceWebhookEvent;

interface MarketplaceWebhookEventRepositoryInterface
{
    public function findByEventId(string $eventId): ?MarketplaceWebhookEvent;

    public function create(ReceiveWebhookEventDTO $data): MarketplaceWebhookEvent;

    public function markProcessed(string $eventId): void;
}
