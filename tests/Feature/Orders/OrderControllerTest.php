<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonImmutable;
use Tests\TestCase;

final class OrderControllerTest extends TestCase
{
    public function test_it_lists_orders_newest_first_in_the_laravel_pagination_shape(): void
    {
        Order::factory()->create(['external_id' => 'ord_old', 'created_at' => CarbonImmutable::parse('2026-01-01T00:00:00+00:00')]);
        Order::factory()->create(['external_id' => 'ord_new', 'created_at' => CarbonImmutable::parse('2026-01-02T00:00:00+00:00')]);

        $response = $this->getJson('/api/orders');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [['id', 'number', 'status', 'customer' => ['email', 'name'], 'total', 'currency', 'created_at', 'updated_at']],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
        $this->assertSame(['ord_new', 'ord_old'], collect($response->json('data'))->pluck('id')->all());
        $this->assertSame(2, $response->json('meta.total'));
    }

    public function test_it_filters_by_status(): void
    {
        Order::factory()->create(['external_id' => 'ord_created']);
        Order::factory()->create(['external_id' => 'ord_paid', 'status' => OrderStatus::Paid, 'paid_at' => now()]);

        $response = $this->getJson('/api/orders?status=paid');

        $response->assertOk();
        $this->assertSame(['ord_paid'], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_it_filters_by_email_case_insensitively(): void
    {
        Order::factory()->create(['external_id' => 'ord_1', 'customer_email' => 'Anna@Example.com']);
        Order::factory()->create(['external_id' => 'ord_2', 'customer_email' => 'other@example.com']);

        $response = $this->getJson('/api/orders?email=anna@example.com');

        $response->assertOk();
        $this->assertSame(['ord_1'], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_it_paginates(): void
    {
        Order::factory()->count(3)->create();

        $response = $this->getJson('/api/orders?per_page=2&page=2');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame(2, $response->json('meta.current_page'));
        $this->assertSame(3, $response->json('meta.total'));
    }

    public function test_it_rejects_an_invalid_status_filter(): void
    {
        $response = $this->getJson('/api/orders?status=refunded');

        $response->assertStatus(422);
    }

    public function test_it_shows_an_order_with_items_and_history(): void
    {
        $order = Order::factory()->create([
            'external_id' => 'ord_7Q2KX9M4',
            'number' => 'MH-1048213',
            'status' => OrderStatus::Shipped,
            'shipment_carrier' => 'СДЭК',
            'shipment_tracking_number' => '1098765432',
            'shipment_tracking_url' => 'https://www.cdek.ru/ru/tracking?order_id=1098765432',
        ]);
        OrderItem::factory()->for($order)->create(['sku' => 'TSHIRT-BLK-M', 'quantity' => 3, 'price' => '1290.00']);
        $order->statusHistory()->create(['status' => OrderStatus::Created, 'changed_at' => CarbonImmutable::parse('2026-01-01T00:00:00+00:00')]);
        $order->statusHistory()->create(['status' => OrderStatus::Shipped, 'changed_at' => CarbonImmutable::parse('2026-01-02T00:00:00+00:00')]);

        $response = $this->getJson("/api/orders/{$order->external_id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', 'ord_7Q2KX9M4');
        $response->assertJsonPath('data.shipment.carrier', 'СДЭК');
        $response->assertJsonPath('data.items.0.subtotal', '3870.00');
        $response->assertJsonPath('data.history.0.status', 'created');
        $response->assertJsonPath('data.history.1.status', 'shipped');
    }

    public function test_it_returns_404_for_a_missing_order(): void
    {
        $response = $this->getJson('/api/orders/ord_missing');

        $response->assertStatus(404);
    }
}
