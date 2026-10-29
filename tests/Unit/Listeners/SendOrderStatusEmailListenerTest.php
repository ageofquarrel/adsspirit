<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Listeners\SendOrderStatusEmailListener;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Mocks\Internal\App\Services\Notifications\SendOrderStatusEmailServiceMock;
use Tests\TestCase;

final class SendOrderStatusEmailListenerTest extends TestCase
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
        SendOrderStatusEmailServiceMock::register();
        $event = new OrderStatusChanged('ord_1', null, $status);

        $result = app(SendOrderStatusEmailListener::class)->shouldQueue($event);

        $this->assertSame($expected, $result);
    }

    public function test_handle_delegates_to_the_service(): void
    {
        SendOrderStatusEmailServiceMock::expectHandle('ord_1', OrderStatus::Paid);
        $event = new OrderStatusChanged('ord_1', OrderStatus::Created, OrderStatus::Paid);

        app(SendOrderStatusEmailListener::class)->handle($event);
    }
}
