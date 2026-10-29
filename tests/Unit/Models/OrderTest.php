<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Tests\TestCase;

final class OrderTest extends TestCase
{
    public function test_amount_fields_are_cast_to_two_decimal_strings(): void
    {
        $order = Order::factory()->make([
            'delivery_price' => 349,
            'discount' => 0,
            'total' => 3619.5,
        ]);

        $this->assertSame('349.00', $order->delivery_price);
        $this->assertSame('0.00', $order->discount);
        $this->assertSame('3619.50', $order->total);
    }

    public function test_status_is_cast_to_enum(): void
    {
        $order = Order::factory()->make(['status' => 'paid']);

        $this->assertSame(OrderStatus::Paid, $order->status);
    }

    public function test_marketplace_timestamps_are_stored_as_given_without_eloquent_autofill(): void
    {
        $createdAt = Carbon::parse('2026-10-21T13:02:11+03:00')->utc();
        $updatedAt = Carbon::parse('2026-10-21T13:05:47+03:00')->utc();

        $order = Order::factory()->create([
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);
        $order->refresh();

        $this->assertTrue($order->created_at->eq($createdAt));
        $this->assertTrue($order->updated_at->eq($updatedAt));
    }

    public function test_it_has_many_items(): void
    {
        $order = Order::factory()->create();
        OrderItem::factory()->for($order)->count(2)->create();

        $items = $order->items;

        $this->assertCount(2, $items);
        $this->assertInstanceOf(OrderItem::class, $items->first());
    }

    public function test_status_history_is_ordered_by_changed_at(): void
    {
        $order = Order::factory()->create();
        $order->statusHistory()->create([
            'status' => OrderStatus::Paid,
            'changed_at' => Carbon::parse('2026-10-21T13:05:47+00:00'),
        ]);
        $order->statusHistory()->create([
            'status' => OrderStatus::Created,
            'changed_at' => Carbon::parse('2026-10-21T13:02:11+00:00'),
        ]);

        $history = $order->statusHistory()->get();

        $this->assertSame(
            [OrderStatus::Created, OrderStatus::Paid],
            $history->pluck('status')->all(),
        );
    }

    public function test_order_has_an_internal_auto_incrementing_id_and_a_marketplace_external_id(): void
    {
        $order = Order::factory()->create(['external_id' => 'ord_7Q2KX9M4']);

        $this->assertIsInt($order->id);
        $this->assertSame('ord_7Q2KX9M4', $order->external_id);

        $found = Order::query()->where('external_id', 'ord_7Q2KX9M4')->first();

        $this->assertNotNull($found);
        $this->assertTrue($found->is($order));
    }

    public function test_external_id_is_unique(): void
    {
        Order::factory()->create(['external_id' => 'ord_1']);

        $this->expectException(QueryException::class);
        Order::factory()->create(['external_id' => 'ord_1']);
    }
}
