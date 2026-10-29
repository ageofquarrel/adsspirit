<?php

declare(strict_types=1);

namespace App\DTO\Marketplace;

use App\Enums\OrderStatus;
use Carbon\CarbonImmutable;

final readonly class OrderSnapshotDTO
{
    /**
     * @param  OrderItemSnapshotDTO[]  $items
     */
    public function __construct(
        public string $externalId,
        public string $number,
        public OrderStatus $status,
        public string $currency,
        public string $customerEmail,
        public string $customerName,
        public ?string $customerPhone,
        public array $items,
        public string $deliveryPrice,
        public string $discount,
        public string $total,
        public ?string $shipmentCarrier,
        public ?string $shipmentTrackingNumber,
        public ?string $shipmentTrackingUrl,
        public ?CarbonImmutable $paidAt,
        public ?CarbonImmutable $cancelledAt,
        public ?string $cancelReason,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}
}
