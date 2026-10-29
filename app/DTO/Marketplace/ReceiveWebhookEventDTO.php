<?php

declare(strict_types=1);

namespace App\DTO\Marketplace;

final readonly class ReceiveWebhookEventDTO
{
    public function __construct(
        public string $eventId,
        public string $type,
        public MarketplaceWebhookPayloadDTO $payload,
    ) {}
}
