<?php

declare(strict_types=1);

namespace App\DTO\Marketplace;

use App\Actions\Marketplace\MapOrderSnapshotAction;

/**
 * Типизированная обёртка над телом вебхука MarketHub (см. task/marketplace-webhooks.md):
 * конверт события (`id`/`type`/`created_at`) плюс сырой снэпшот `data.order`, который
 * дальше размечается в {@see MapOrderSnapshotAction}.
 */
final readonly class MarketplaceWebhookPayloadDTO
{
    /**
     * @param  array<string, mixed>  $order
     */
    public function __construct(
        public string $id,
        public string $type,
        public string $createdAt,
        public array $order,
    ) {}
}
