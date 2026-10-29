<?php

namespace App\Repositories\Contracts;

use App\Enums\OrderStatus;

interface OrderNotificationRepositoryInterface
{
    /**
     * Atomically claims the right to send this notification.
     *
     * @return bool `true` the first time it's called for this (order, type) pair,
     *              `false` on every subsequent call (already sent before).
     */
    public function reserve(string $orderId, OrderStatus $type): bool;
}
