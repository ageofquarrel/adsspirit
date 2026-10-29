<?php

namespace Tests\Unit\Resources;

use App\Enums\OrderStatus;
use App\Http\Resources\OrderListItemResource;
use App\Models\Order;
use Tests\TestCase;

class OrderListItemResourceTest extends TestCase
{
    public function test_it_exposes_only_the_list_fields(): void
    {
        // Arrange
        $order = Order::factory()->create([
            'id' => 'ord_1',
            'number' => 'MH-1',
            'status' => OrderStatus::Paid,
            'customer_email' => 'anna@example.com',
            'customer_name' => 'Anna',
            'total' => '100.00',
            'currency' => 'RUB',
        ]);

        // Act
        $array = (new OrderListItemResource($order))->resolve();

        // Assert
        $this->assertSame([
            'id' => 'ord_1',
            'number' => 'MH-1',
            'status' => 'paid',
            'customer' => ['email' => 'anna@example.com', 'name' => 'Anna'],
            'total' => '100.00',
            'currency' => 'RUB',
            'created_at' => $order->created_at->toAtomString(),
            'updated_at' => $order->updated_at->toAtomString(),
        ], $array);
    }
}
