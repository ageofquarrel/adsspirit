<?php

declare(strict_types=1);

namespace App\Actions\Marketplace;

use App\DTO\Marketplace\MarketplaceWebhookPayloadDTO;
use App\DTO\Marketplace\ReceiveWebhookEventDTO;
use App\Http\Requests\Marketplace\StoreMarketplaceWebhookRequest;

/**
 * Преобразует провалидированное тело вебхука из
 * {@see StoreMarketplaceWebhookRequest} в {@see ReceiveWebhookEventDTO}.
 */
final class MapReceiveWebhookEventAction
{
    /**
     * @param  array<string, mixed>  $payload  Сырое тело вебхука (см. task/marketplace-webhooks.md).
     */
    public function execute(array $payload): ReceiveWebhookEventDTO
    {
        return new ReceiveWebhookEventDTO(
            eventId: $payload['id'],
            type: $payload['type'],
            payload: new MarketplaceWebhookPayloadDTO(
                id: $payload['id'],
                type: $payload['type'],
                createdAt: $payload['created_at'],
                order: $payload['data']['order'],
            ),
        );
    }
}
