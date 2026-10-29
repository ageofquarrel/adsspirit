<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    public function test_it_lists_orders(): void
    {
        // Arrange
        Order::factory()->create(['number' => 'MH-1001', 'customer_email' => 'anna@example.com']);

        // Act
        $response = $this->get('/admin/orders');

        // Assert
        $response->assertOk();
        $response->assertSee('MH-1001');
        $response->assertSee('anna@example.com');
    }

    public function test_it_filters_by_status(): void
    {
        // Arrange
        Order::factory()->create(['number' => 'MH-CREATED']);
        Order::factory()->create(['number' => 'MH-PAID', 'status' => OrderStatus::Paid, 'paid_at' => now()]);

        // Act
        $response = $this->get('/admin/orders?status=paid');

        // Assert
        $response->assertOk();
        $response->assertSee('MH-PAID');
        $response->assertDontSee('MH-CREATED');
    }

    public function test_it_filters_by_email(): void
    {
        // Arrange
        Order::factory()->create(['number' => 'MH-A', 'customer_email' => 'anna@example.com']);
        Order::factory()->create(['number' => 'MH-B', 'customer_email' => 'other@example.com']);

        // Act
        $response = $this->get('/admin/orders?email=anna@example.com');

        // Assert
        $response->assertOk();
        $response->assertSee('MH-A');
        $response->assertDontSee('MH-B');
    }

    public function test_it_shows_an_order_with_items_and_history(): void
    {
        // Arrange
        $order = Order::factory()->create([
            'number' => 'MH-DETAIL',
            'status' => OrderStatus::Shipped,
            'shipment_carrier' => 'СДЭК',
        ]);
        OrderItem::factory()->for($order)->create(['sku' => 'SKU-X', 'name' => 'Товар X']);
        $order->statusHistory()->create(['status' => OrderStatus::Created, 'changed_at' => now()->subDay()]);
        $order->statusHistory()->create(['status' => OrderStatus::Shipped, 'changed_at' => now()]);

        // Act
        $response = $this->get("/admin/orders/{$order->id}");

        // Assert
        $response->assertOk();
        $response->assertSee('MH-DETAIL');
        $response->assertSee('SKU-X');
        $response->assertSee('Товар X');
        $response->assertSee('СДЭК');
    }

    public function test_it_returns_404_for_a_missing_order(): void
    {
        // Act
        $response = $this->get('/admin/orders/does-not-exist');

        // Assert
        $response->assertStatus(404);
    }
}
