<?php

namespace Tests\Doubles\Services;

use App\Enums\OrderStatus;
use App\Services\Notifications\SendOrderStatusEmailService;

/**
 * Test double for {@see SendOrderStatusEmailService}, which is already
 * covered by its own test.
 */
final class SendOrderStatusEmailServiceMock extends SendOrderStatusEmailService
{
    public int $callCount = 0;

    public ?string $lastOrderId = null;

    public ?OrderStatus $lastType = null;

    public function __construct() {}

    public function handle(string $orderId, OrderStatus $type): void
    {
        $this->callCount++;
        $this->lastOrderId = $orderId;
        $this->lastType = $type;
    }
}
