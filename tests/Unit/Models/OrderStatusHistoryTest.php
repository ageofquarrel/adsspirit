<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\OrderStatus;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Tests\TestCase;

final class OrderStatusHistoryTest extends TestCase
{
    public function test_status_is_cast_to_enum(): void
    {
        $order = Order::factory()->create();

        $history = $order->statusHistory()->create([
            'status' => OrderStatus::Paid,
            'changed_at' => Carbon::now(),
        ]);

        $this->assertSame(OrderStatus::Paid, $history->status);
    }

    public function test_the_same_status_cannot_be_recorded_twice_for_the_same_order(): void
    {
        $order = Order::factory()->create();
        $order->statusHistory()->create([
            'status' => OrderStatus::Paid,
            'changed_at' => Carbon::now(),
        ]);

        $this->expectException(QueryException::class);
        $order->statusHistory()->create([
            'status' => OrderStatus::Paid,
            'changed_at' => Carbon::now(),
        ]);
    }
}
