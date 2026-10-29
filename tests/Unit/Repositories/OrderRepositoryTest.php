<?php

namespace Tests\Unit\Repositories;

use App\DTO\Marketplace\OrderItemSnapshotDTO;
use App\DTO\Marketplace\OrderSnapshotDTO;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Repositories\OrderRepository;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class OrderRepositoryTest extends TestCase
{
    private function snapshot(array $overrides = []): OrderSnapshotDTO
    {
        return new OrderSnapshotDTO(
            id: $overrides['id'] ?? 'ord_1',
            number: $overrides['number'] ?? 'MH-1',
            status: $overrides['status'] ?? OrderStatus::Created,
            currency: 'RUB',
            customerEmail: 'a@example.com',
            customerName: 'Anna',
            customerPhone: null,
            items: $overrides['items'] ?? [
                new OrderItemSnapshotDTO('SKU-1', 'Item 1', 2, '100.00'),
            ],
            deliveryPrice: '0.00',
            discount: '0.00',
            total: '200.00',
            shipmentCarrier: null,
            shipmentTrackingNumber: null,
            shipmentTrackingUrl: null,
            paidAt: $overrides['paidAt'] ?? null,
            cancelledAt: null,
            cancelReason: null,
            createdAt: CarbonImmutable::parse('2026-01-01T10:00:00+00:00'),
            updatedAt: $overrides['updatedAt'] ?? CarbonImmutable::parse('2026-01-01T10:00:00+00:00'),
        );
    }

    public function test_find_returns_null_when_the_order_does_not_exist(): void
    {
        // Arrange
        $repository = new OrderRepository;

        // Act & Assert
        $this->assertNull($repository->find('ord_missing'));
    }

    public function test_find_returns_the_stored_order(): void
    {
        // Arrange
        $repository = new OrderRepository;
        $repository->applySnapshot($this->snapshot(), CarbonImmutable::now());

        // Act
        $order = $repository->find('ord_1');

        // Assert
        $this->assertNotNull($order);
        $this->assertSame('ord_1', $order->id);
    }

    public function test_apply_snapshot_creates_the_order_with_its_items_and_initial_history(): void
    {
        // Arrange
        $repository = new OrderRepository;
        $changedAt = CarbonImmutable::parse('2026-01-01T10:00:00+00:00');

        // Act
        $order = $repository->applySnapshot($this->snapshot(), $changedAt);

        // Assert
        $this->assertSame('ord_1', $order->id);
        $this->assertCount(1, $order->items);
        $this->assertSame('SKU-1', $order->items->first()->sku);
        $this->assertCount(1, $order->statusHistory);
        $this->assertSame(OrderStatus::Created, $order->statusHistory->first()->status);
    }

    public function test_apply_snapshot_replaces_items_on_update(): void
    {
        // Arrange
        $repository = new OrderRepository;
        $repository->applySnapshot($this->snapshot(), CarbonImmutable::now());

        // Act
        $updated = $repository->applySnapshot($this->snapshot([
            'items' => [new OrderItemSnapshotDTO('SKU-2', 'Item 2', 1, '50.00')],
            'updatedAt' => CarbonImmutable::parse('2026-01-02T10:00:00+00:00'),
        ]), CarbonImmutable::now());

        // Assert
        $this->assertCount(1, $updated->items);
        $this->assertSame('SKU-2', $updated->items->first()->sku);
    }

    public function test_apply_snapshot_adds_a_new_history_row_for_a_new_status(): void
    {
        // Arrange
        $repository = new OrderRepository;
        $repository->applySnapshot($this->snapshot(), CarbonImmutable::parse('2026-01-01T10:00:00+00:00'));

        // Act
        $order = $repository->applySnapshot($this->snapshot([
            'status' => OrderStatus::Paid,
            'paidAt' => CarbonImmutable::parse('2026-01-02T10:00:00+00:00'),
            'updatedAt' => CarbonImmutable::parse('2026-01-02T10:00:00+00:00'),
        ]), CarbonImmutable::parse('2026-01-02T10:00:00+00:00'));

        // Assert
        $this->assertCount(2, $order->statusHistory);
        $this->assertSame(
            [OrderStatus::Created, OrderStatus::Paid],
            $order->statusHistory->pluck('status')->all(),
        );
    }

    public function test_apply_snapshot_updates_the_timestamp_when_the_same_status_is_seen_again(): void
    {
        // Arrange
        $repository = new OrderRepository;
        $repository->applySnapshot($this->snapshot(), CarbonImmutable::parse('2026-01-01T10:00:00+00:00'));

        // Act — a correction from support resends the same status with a new timestamp.
        $order = $repository->applySnapshot(
            $this->snapshot(['updatedAt' => CarbonImmutable::parse('2026-01-03T10:00:00+00:00')]),
            CarbonImmutable::parse('2026-01-03T09:00:00+00:00'),
        );

        // Assert
        $this->assertCount(1, $order->statusHistory);
        $this->assertTrue(
            CarbonImmutable::parse('2026-01-03T09:00:00+00:00')->eq($order->statusHistory->first()->changed_at),
        );
    }

    public function test_apply_snapshot_is_idempotent_for_the_same_input(): void
    {
        // Arrange
        $repository = new OrderRepository;
        $snapshot = $this->snapshot();

        // Act
        $repository->applySnapshot($snapshot, CarbonImmutable::now());
        $repository->applySnapshot($snapshot, CarbonImmutable::now());

        // Assert
        $this->assertSame(1, Order::query()->count());
    }
}
