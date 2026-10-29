<?php

namespace App\DTO\Marketplace;

use App\Enums\OrderStatus;
use App\Models\Order;

final readonly class ApplyOrderEventResultDTO
{
    public function __construct(
        /** Whether the snapshot was actually applied (false when it was stale/out of order). */
        public bool $applied,
        /** Whether this call made the order transition into a new status. */
        public bool $statusChanged,
        public ?OrderStatus $previousStatus,
        public OrderStatus $newStatus,
        public Order $order,
    ) {}
}
