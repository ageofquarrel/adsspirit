<?php

declare(strict_types=1);

namespace Tests\Unit\Resources;

use App\Enums\OrderStatus;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonImmutable;
use Tests\TestCase;

final class OrderResourceTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function toArray(OrderResource $resource): array
    {
        return json_decode(json_encode($resource), true);
    }

    public function test_it_computes_item_subtotals(): void
    {
        $order = Order::factory()->create();
        OrderItem::factory()->for($order)->create(['price' => '100.00', 'quantity' => 3]);
        $order->load('items', 'statusHistory');

        $array = $this->toArray(new OrderResource($order));

        $this->assertSame('300.00', $array['items'][0]['subtotal']);
    }

    public function test_shipment_is_null_when_the_order_has_not_shipped(): void
    {
        $order = Order::factory()->create(['shipment_carrier' => null]);
        $order->load('items', 'statusHistory');

        $array = $this->toArray(new OrderResource($order));

        $this->assertNull($array['shipment']);
    }

    public function test_shipment_is_present_once_the_order_has_shipped(): void
    {
        $order = Order::factory()->create([
            'shipment_carrier' => 'СДЭК',
            'shipment_tracking_number' => '123',
            'shipment_tracking_url' => 'https://example.com/track',
        ]);
        $order->load('items', 'statusHistory');

        $array = $this->toArray(new OrderResource($order));

        $this->assertSame([
            'carrier' => 'СДЭК',
            'tracking_number' => '123',
            'tracking_url' => 'https://example.com/track',
        ], $array['shipment']);
    }

    public function test_history_is_returned_in_chronological_order(): void
    {
        $order = Order::factory()->create();
        $order->statusHistory()->create(['status' => OrderStatus::Paid, 'changed_at' => CarbonImmutable::parse('2026-01-02T00:00:00+00:00')]);
        $order->statusHistory()->create(['status' => OrderStatus::Created, 'changed_at' => CarbonImmutable::parse('2026-01-01T00:00:00+00:00')]);
        $order->load('items', 'statusHistory');

        $array = $this->toArray(new OrderResource($order));

        $this->assertSame(
            ['created', 'paid'],
            array_column($array['history'], 'status'),
        );
    }
}
