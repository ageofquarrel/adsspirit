<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Tests\TestCase;

final class OrderControllerTest extends TestCase
{
    public function test_it_lists_orders(): void
    {
        Order::factory()->create(['number' => 'MH-1001', 'customer_email' => 'anna@example.com']);

        $response = $this->get('/admin/orders');

        $response->assertOk();
        $response->assertSee('MH-1001');
        $response->assertSee('anna@example.com');
    }

    public function test_it_filters_by_status(): void
    {
        Order::factory()->create(['number' => 'MH-CREATED']);
        Order::factory()->create(['number' => 'MH-PAID', 'status' => OrderStatus::Paid, 'paid_at' => now()]);

        $response = $this->get('/admin/orders?status=paid');

        $response->assertOk();
        $response->assertSee('MH-PAID');
        $response->assertDontSee('MH-CREATED');
    }

    public function test_it_filters_by_email(): void
    {
        Order::factory()->create(['number' => 'MH-A', 'customer_email' => 'anna@example.com']);
        Order::factory()->create(['number' => 'MH-B', 'customer_email' => 'other@example.com']);

        $response = $this->get('/admin/orders?email=anna@example.com');

        $response->assertOk();
        $response->assertSee('MH-A');
        $response->assertDontSee('MH-B');
    }

    public function test_it_shows_an_order_with_items_and_history(): void
    {
        $order = Order::factory()->create([
            'number' => 'MH-DETAIL',
            'status' => OrderStatus::Shipped,
            'shipment_carrier' => 'СДЭК',
        ]);
        OrderItem::factory()->for($order)->create(['sku' => 'SKU-X', 'name' => 'Товар X']);
        $order->statusHistory()->create(['status' => OrderStatus::Created, 'changed_at' => now()->subDay()]);
        $order->statusHistory()->create(['status' => OrderStatus::Shipped, 'changed_at' => now()]);

        $response = $this->get("/admin/orders/{$order->external_id}");

        $response->assertOk();
        $response->assertSee('MH-DETAIL');
        $response->assertSee('SKU-X');
        $response->assertSee('Товар X');
        $response->assertSee('СДЭК');
    }

    public function test_it_returns_404_for_a_missing_order(): void
    {
        $response = $this->get('/admin/orders/does-not-exist');

        $response->assertStatus(404);
    }
}
