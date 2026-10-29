<?php

namespace Tests\Unit\Models;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Tests\TestCase;

class OrderTest extends TestCase
{
    public function test_amount_fields_are_cast_to_two_decimal_strings(): void
    {
        // Arrange
        $order = Order::factory()->make([
            'delivery_price' => 349,
            'discount' => 0,
            'total' => 3619.5,
        ]);

        // Act & Assert
        $this->assertSame('349.00', $order->delivery_price);
        $this->assertSame('0.00', $order->discount);
        $this->assertSame('3619.50', $order->total);
    }

    public function test_status_is_cast_to_enum(): void
    {
        // Arrange
        $order = Order::factory()->make(['status' => 'paid']);

        // Act & Assert
        $this->assertSame(OrderStatus::Paid, $order->status);
    }

    public function test_marketplace_timestamps_are_stored_as_given_without_eloquent_autofill(): void
    {
        // Arrange: datetime columns have no timezone of their own, so values are
        // normalized to UTC before being persisted — same as the real webhook
        // processor will do.
        $createdAt = Carbon::parse('2026-10-21T13:02:11+03:00')->utc();
        $updatedAt = Carbon::parse('2026-10-21T13:05:47+03:00')->utc();

        // Act
        $order = Order::factory()->create([
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);
        $order->refresh();

        // Assert
        $this->assertTrue($order->created_at->eq($createdAt));
        $this->assertTrue($order->updated_at->eq($updatedAt));
    }

    public function test_it_has_many_items(): void
    {
        // Arrange
        $order = Order::factory()->create();
        OrderItem::factory()->for($order)->count(2)->create();

        // Act
        $items = $order->items;

        // Assert
        $this->assertCount(2, $items);
        $this->assertInstanceOf(OrderItem::class, $items->first());
    }

    public function test_status_history_is_ordered_by_changed_at(): void
    {
        // Arrange
        $order = Order::factory()->create();
        $order->statusHistory()->create([
            'status' => OrderStatus::Paid,
            'changed_at' => Carbon::parse('2026-10-21T13:05:47+00:00'),
        ]);
        $order->statusHistory()->create([
            'status' => OrderStatus::Created,
            'changed_at' => Carbon::parse('2026-10-21T13:02:11+00:00'),
        ]);

        // Act
        $history = $order->statusHistory()->get();

        // Assert
        $this->assertSame(
            [OrderStatus::Created, OrderStatus::Paid],
            $history->pluck('status')->all(),
        );
    }

    public function test_order_primary_key_is_the_marketplace_id_string(): void
    {
        // Arrange
        $order = Order::factory()->create(['id' => 'ord_7Q2KX9M4']);

        // Act
        $found = Order::query()->find('ord_7Q2KX9M4');

        // Assert
        $this->assertNotNull($found);
        $this->assertTrue($found->is($order));
    }
}
