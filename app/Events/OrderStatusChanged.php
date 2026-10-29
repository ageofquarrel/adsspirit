<?php

namespace App\Events;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired whenever applying a marketplace snapshot moves an order into a new
 * status (never for a stale/out-of-order snapshot, and never when the
 * status didn't actually change, e.g. an `order.updated` event). Deciding
 * *what* to do about it (send an email today, maybe notify ops or sync to
 * analytics tomorrow) is left entirely to listeners.
 */
final readonly class OrderStatusChanged
{
    use Dispatchable;

    public function __construct(
        public string $orderId,
        public ?OrderStatus $previousStatus,
        public OrderStatus $newStatus,
    ) {}
}
