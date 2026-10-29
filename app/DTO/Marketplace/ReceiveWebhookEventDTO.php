<?php

namespace App\DTO\Marketplace;

final readonly class ReceiveWebhookEventDTO
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $eventId,
        public string $type,
        public array $payload,
    ) {}
}
