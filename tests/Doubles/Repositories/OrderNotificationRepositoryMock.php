<?php

namespace Tests\Doubles\Repositories;

use App\Enums\OrderStatus;
use App\Repositories\Contracts\OrderNotificationRepositoryInterface;
use App\Repositories\OrderNotificationRepository;

/**
 * Test double for {@see OrderNotificationRepository}, which is already
 * covered by its own repository test.
 */
final class OrderNotificationRepositoryMock implements OrderNotificationRepositoryInterface
{
    public bool $reserveReturn = true;

    public int $reserveCallCount = 0;

    public ?string $lastOrderId = null;

    public ?OrderStatus $lastType = null;

    public function reserve(string $orderId, OrderStatus $type): bool
    {
        $this->reserveCallCount++;
        $this->lastOrderId = $orderId;
        $this->lastType = $type;

        return $this->reserveReturn;
    }
}
