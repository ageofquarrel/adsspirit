<?php

namespace Tests\Unit\Models;

use App\Enums\OrderStatus;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class OrderStatusHistoryTest extends TestCase
{
    public function test_status_is_cast_to_enum(): void
    {
        // Arrange
        $order = Order::factory()->create();

        // Act
        $history = $order->statusHistory()->create([
            'status' => OrderStatus::Paid,
            'changed_at' => Carbon::now(),
        ]);

        // Assert
        $this->assertSame(OrderStatus::Paid, $history->status);
    }

    public function test_the_same_status_cannot_be_recorded_twice_for_the_same_order(): void
    {
        // Arrange
        $order = Order::factory()->create();
        $order->statusHistory()->create([
            'status' => OrderStatus::Paid,
            'changed_at' => Carbon::now(),
        ]);

        // Act & Assert
        $this->expectException(QueryException::class);
        $order->statusHistory()->create([
            'status' => OrderStatus::Paid,
            'changed_at' => Carbon::now(),
        ]);
    }
}
