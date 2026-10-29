<?php

namespace App\DTO\Marketplace;

final readonly class OrderItemSnapshotDTO
{
    public function __construct(
        public string $sku,
        public string $name,
        public int $quantity,
        public string $price,
    ) {}
}
