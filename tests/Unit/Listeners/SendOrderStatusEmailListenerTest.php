<?php

namespace Tests\Unit\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Listeners\SendOrderStatusEmailListener;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Doubles\Services\SendOrderStatusEmailServiceMock;
use Tests\TestCase;

class SendOrderStatusEmailListenerTest extends TestCase
{
    /**
     * @return array<string, array{0: OrderStatus, 1: bool}>
     */
    public static function cases(): array
    {
        return [
            'paid is queued' => [OrderStatus::Paid, true],
            'shipped is queued' => [OrderStatus::Shipped, true],
            'cancelled is queued' => [OrderStatus::Cancelled, true],
            'created is not queued' => [OrderStatus::Created, false],
            'delivered is not queued' => [OrderStatus::Delivered, false],
        ];
    }

    #[DataProvider('cases')]
    public function test_should_queue_reflects_whether_the_status_has_an_email(OrderStatus $status, bool $expected): void
    {
        // Arrange
        $listener = new SendOrderStatusEmailListener(new SendOrderStatusEmailServiceMock);
        $event = new OrderStatusChanged('ord_1', null, $status);

        // Act & Assert
        $this->assertSame($expected, $listener->shouldQueue($event));
    }

    public function test_handle_delegates_to_the_service(): void
    {
        // Arrange
        $service = new SendOrderStatusEmailServiceMock;
        $listener = new SendOrderStatusEmailListener($service);
        $event = new OrderStatusChanged('ord_1', OrderStatus::Created, OrderStatus::Paid);

        // Act
        $listener->handle($event);

        // Assert
        $this->assertSame(1, $service->callCount);
        $this->assertSame('ord_1', $service->lastOrderId);
        $this->assertSame(OrderStatus::Paid, $service->lastType);
    }
}
