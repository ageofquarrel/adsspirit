<?php

namespace Tests\Unit\Services\Marketplace;

use App\DTO\Marketplace\OrderItemSnapshotDTO;
use App\DTO\Marketplace\OrderSnapshotDTO;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Marketplace\ApplyOrderEventService;
use Carbon\CarbonImmutable;
use Tests\Doubles\Actions\MapOrderSnapshotActionMock;
use Tests\Doubles\Actions\ResolveOrderStatusTimestampActionMock;
use Tests\Doubles\Repositories\OrderRepositoryMock;
use Tests\TestCase;

class ApplyOrderEventServiceTest extends TestCase
{
    private function snapshot(OrderStatus $status, CarbonImmutable $updatedAt): OrderSnapshotDTO
    {
        return new OrderSnapshotDTO(
            id: 'ord_1',
            number: 'MH-1',
            status: $status,
            currency: 'RUB',
            customerEmail: 'a@example.com',
            customerName: 'Anna',
            customerPhone: null,
            items: [new OrderItemSnapshotDTO('SKU-1', 'Item', 1, '100.00')],
            deliveryPrice: '0.00',
            discount: '0.00',
            total: '100.00',
            shipmentCarrier: null,
            shipmentTrackingNumber: null,
            shipmentTrackingUrl: null,
            paidAt: null,
            cancelledAt: null,
            cancelReason: null,
            createdAt: CarbonImmutable::parse('2026-01-01T10:00:00+00:00'),
            updatedAt: $updatedAt,
        );
    }

    public function test_a_first_time_order_is_always_applied_as_a_status_change(): void
    {
        // Arrange
        $snapshot = $this->snapshot(OrderStatus::Created, CarbonImmutable::parse('2026-01-01T10:00:00+00:00'));
        $statusChangedAt = CarbonImmutable::parse('2026-01-01T10:00:00+00:00');

        $orderRepository = new OrderRepositoryMock;
        $orderRepository->findReturn = null;

        $service = new ApplyOrderEventService(
            $orderRepository,
            new MapOrderSnapshotActionMock($snapshot),
            new ResolveOrderStatusTimestampActionMock($statusChangedAt),
        );

        // Act
        $result = $service->handle([]);

        // Assert
        $this->assertTrue($result->applied);
        $this->assertTrue($result->statusChanged);
        $this->assertNull($result->previousStatus);
        $this->assertSame(OrderStatus::Created, $result->newStatus);
        $this->assertSame(1, $orderRepository->applySnapshotCallCount);
        $this->assertSame($snapshot, $orderRepository->lastSnapshot);
    }

    public function test_a_newer_snapshot_that_changes_status_is_applied(): void
    {
        // Arrange
        $existing = new Order([
            'id' => 'ord_1',
            'status' => OrderStatus::Created,
            'updated_at' => CarbonImmutable::parse('2026-01-01T10:00:00+00:00'),
        ]);
        $snapshot = $this->snapshot(OrderStatus::Paid, CarbonImmutable::parse('2026-01-02T10:00:00+00:00'));

        $orderRepository = new OrderRepositoryMock;
        $orderRepository->findReturn = $existing;

        $service = new ApplyOrderEventService(
            $orderRepository,
            new MapOrderSnapshotActionMock($snapshot),
            new ResolveOrderStatusTimestampActionMock(CarbonImmutable::now()),
        );

        // Act
        $result = $service->handle([]);

        // Assert
        $this->assertTrue($result->applied);
        $this->assertTrue($result->statusChanged);
        $this->assertSame(OrderStatus::Created, $result->previousStatus);
        $this->assertSame(OrderStatus::Paid, $result->newStatus);
        $this->assertSame(1, $orderRepository->applySnapshotCallCount);
    }

    public function test_a_newer_snapshot_with_the_same_status_is_applied_without_a_status_change(): void
    {
        // Arrange — e.g. an order.updated event that doesn't touch the status.
        $existing = new Order([
            'id' => 'ord_1',
            'status' => OrderStatus::Created,
            'updated_at' => CarbonImmutable::parse('2026-01-01T10:00:00+00:00'),
        ]);
        $snapshot = $this->snapshot(OrderStatus::Created, CarbonImmutable::parse('2026-01-02T10:00:00+00:00'));

        $orderRepository = new OrderRepositoryMock;
        $orderRepository->findReturn = $existing;

        $service = new ApplyOrderEventService(
            $orderRepository,
            new MapOrderSnapshotActionMock($snapshot),
            new ResolveOrderStatusTimestampActionMock(CarbonImmutable::now()),
        );

        // Act
        $result = $service->handle([]);

        // Assert
        $this->assertTrue($result->applied);
        $this->assertFalse($result->statusChanged);
    }

    public function test_a_stale_or_replayed_snapshot_is_ignored(): void
    {
        // Arrange
        $existing = new Order([
            'id' => 'ord_1',
            'status' => OrderStatus::Paid,
            'updated_at' => CarbonImmutable::parse('2026-01-02T10:00:00+00:00'),
        ]);
        $staleSnapshot = $this->snapshot(OrderStatus::Created, CarbonImmutable::parse('2026-01-01T10:00:00+00:00'));

        $orderRepository = new OrderRepositoryMock;
        $orderRepository->findReturn = $existing;

        $service = new ApplyOrderEventService(
            $orderRepository,
            new MapOrderSnapshotActionMock($staleSnapshot),
            new ResolveOrderStatusTimestampActionMock(CarbonImmutable::now()),
        );

        // Act
        $result = $service->handle([]);

        // Assert
        $this->assertFalse($result->applied);
        $this->assertFalse($result->statusChanged);
        $this->assertSame(OrderStatus::Paid, $result->newStatus);
        $this->assertSame(0, $orderRepository->applySnapshotCallCount);
    }

    public function test_a_snapshot_with_the_exact_same_updated_at_is_treated_as_a_replay_and_ignored(): void
    {
        // Arrange
        $sameInstant = CarbonImmutable::parse('2026-01-01T10:00:00+00:00');
        $existing = new Order([
            'id' => 'ord_1',
            'status' => OrderStatus::Created,
            'updated_at' => $sameInstant,
        ]);
        $snapshot = $this->snapshot(OrderStatus::Created, $sameInstant);

        $orderRepository = new OrderRepositoryMock;
        $orderRepository->findReturn = $existing;

        $service = new ApplyOrderEventService(
            $orderRepository,
            new MapOrderSnapshotActionMock($snapshot),
            new ResolveOrderStatusTimestampActionMock(CarbonImmutable::now()),
        );

        // Act
        $result = $service->handle([]);

        // Assert
        $this->assertFalse($result->applied);
        $this->assertSame(0, $orderRepository->applySnapshotCallCount);
    }
}
