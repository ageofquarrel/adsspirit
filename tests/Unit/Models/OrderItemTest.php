<?php

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\OrderItem;
use Tests\TestCase;

class OrderItemTest extends TestCase
{
    public function test_price_is_cast_to_two_decimal_string_and_quantity_to_integer(): void
    {
        // Arrange
        $item = OrderItem::factory()->make([
            'price' => 1290,
            'quantity' => '3',
        ]);

        // Act & Assert
        $this->assertSame('1290.00', $item->price);
        $this->assertSame(3, $item->quantity);
    }

    public function test_it_belongs_to_an_order(): void
    {
        // Arrange
        $order = Order::factory()->create();
        $item = OrderItem::factory()->for($order)->create();

        // Act
        $relatedOrder = $item->order;

        // Assert
        $this->assertTrue($relatedOrder->is($order));
    }
}
