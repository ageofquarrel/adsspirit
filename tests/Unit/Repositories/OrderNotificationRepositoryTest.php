<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderNotification;
use App\Repositories\OrderNotificationRepository;
use Tests\TestCase;

final class OrderNotificationRepositoryTest extends TestCase
{
    public function test_reserve_succeeds_the_first_time(): void
    {
        $order = Order::factory()->create();
        $repository = new OrderNotificationRepository;

        $reserved = $repository->reserve($order->id, OrderStatus::Paid);

        $this->assertTrue($reserved);
        $this->assertDatabaseHas('order_notifications', [
            'order_id' => $order->id,
            'type' => OrderStatus::Paid->value,
        ]);
    }

    public function test_reserve_fails_on_a_second_call_for_the_same_order_and_type(): void
    {
        $order = Order::factory()->create();
        $repository = new OrderNotificationRepository;
        $repository->reserve($order->id, OrderStatus::Paid);

        $reserved = $repository->reserve($order->id, OrderStatus::Paid);

        $this->assertFalse($reserved);
        $this->assertSame(
            1,
            OrderNotification::query()
                ->where('order_id', $order->id)
                ->where('type', OrderStatus::Paid->value)
                ->count(),
        );
    }

    public function test_reserve_succeeds_independently_for_different_types(): void
    {
        $order = Order::factory()->create();
        $repository = new OrderNotificationRepository;
        $repository->reserve($order->id, OrderStatus::Paid);

        $reserved = $repository->reserve($order->id, OrderStatus::Shipped);

        $this->assertTrue($reserved);
    }
}
