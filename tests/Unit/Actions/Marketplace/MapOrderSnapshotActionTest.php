<?php

namespace Tests\Unit\Actions\Marketplace;

use App\Actions\Marketplace\MapOrderSnapshotAction;
use App\Enums\OrderStatus;
use Tests\TestCase;

class MapOrderSnapshotActionTest extends TestCase
{
    private function loadOrder(string $exampleFile): array
    {
        $event = json_decode(file_get_contents(base_path("task/examples/{$exampleFile}")), true);

        return $event['data']['order'];
    }

    public function test_it_maps_a_paid_order_without_shipment(): void
    {
        // Arrange
        $raw = $this->loadOrder('order-paid.json');
        $action = new MapOrderSnapshotAction;

        // Act
        $snapshot = $action($raw);

        // Assert
        $this->assertSame('ord_7Q2KX9M4', $snapshot->id);
        $this->assertSame('MH-1048213', $snapshot->number);
        $this->assertSame(OrderStatus::Paid, $snapshot->status);
        $this->assertSame('RUB', $snapshot->currency);
        $this->assertSame('anna.smirnova@example.com', $snapshot->customerEmail);
        $this->assertSame('+79161234567', $snapshot->customerPhone);
        $this->assertCount(2, $snapshot->items);
        $this->assertSame('TSHIRT-BLK-M', $snapshot->items[0]->sku);
        $this->assertSame(3, $snapshot->items[0]->quantity);
        $this->assertSame('1290.00', $snapshot->items[0]->price);
        $this->assertSame('349.00', $snapshot->deliveryPrice);
        $this->assertSame('4909.50', $snapshot->total);
        $this->assertNull($snapshot->shipmentCarrier);
        $this->assertNotNull($snapshot->paidAt);
        $this->assertSame('2026-10-21T10:05:47+00:00', $snapshot->paidAt->toIso8601String());
        $this->assertNull($snapshot->cancelledAt);
        $this->assertSame('2026-10-21T10:02:11+00:00', $snapshot->createdAt->toIso8601String());
        $this->assertSame('2026-10-21T10:05:47+00:00', $snapshot->updatedAt->toIso8601String());
    }

    public function test_it_maps_a_shipped_order_with_shipment_details(): void
    {
        // Arrange
        $raw = $this->loadOrder('order-shipped.json');
        $action = new MapOrderSnapshotAction;

        // Act
        $snapshot = $action($raw);

        // Assert
        $this->assertSame(OrderStatus::Shipped, $snapshot->status);
        $this->assertSame('СДЭК', $snapshot->shipmentCarrier);
        $this->assertSame('1098765432', $snapshot->shipmentTrackingNumber);
        $this->assertSame('https://www.cdek.ru/ru/tracking?order_id=1098765432', $snapshot->shipmentTrackingUrl);
    }

    public function test_it_maps_a_cancelled_order_with_a_reason(): void
    {
        // Arrange
        $raw = $this->loadOrder('order-cancelled.json');
        $action = new MapOrderSnapshotAction;

        // Act
        $snapshot = $action($raw);

        // Assert
        $this->assertSame(OrderStatus::Cancelled, $snapshot->status);
        $this->assertSame('Покупатель отказался от заказа', $snapshot->cancelReason);
        $this->assertNotNull($snapshot->cancelledAt);
        $this->assertNotNull($snapshot->paidAt);
    }

    public function test_it_maps_a_freshly_created_order_with_null_optional_fields(): void
    {
        // Arrange
        $raw = $this->loadOrder('order-created.json');
        $action = new MapOrderSnapshotAction;

        // Act
        $snapshot = $action($raw);

        // Assert
        $this->assertSame(OrderStatus::Created, $snapshot->status);
        $this->assertNull($snapshot->paidAt);
        $this->assertNull($snapshot->cancelledAt);
        $this->assertNull($snapshot->cancelReason);
        $this->assertNull($snapshot->shipmentCarrier);
    }
}
