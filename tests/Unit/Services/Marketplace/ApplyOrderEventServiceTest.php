<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Marketplace;

use App\DTO\Marketplace\OrderItemSnapshotDTO;
use App\DTO\Marketplace\OrderSnapshotDTO;
use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Services\Marketplace\ApplyOrderEventService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Internal\App\Actions\Marketplace\MapOrderSnapshotActionMock;
use Tests\Mocks\Internal\App\Actions\Marketplace\ResolveOrderStatusTimestampActionMock;
use Tests\Mocks\Internal\App\Repositories\Contracts\OrderRepositoryMock;
use Tests\TestCase;

final class ApplyOrderEventServiceTest extends TestCase
{
    private function snapshot(OrderStatus $status, CarbonImmutable $updatedAt): OrderSnapshotDTO
    {
        return new OrderSnapshotDTO(
            externalId: 'ord_1',
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
        Event::fake();
        $snapshot = $this->snapshot(OrderStatus::Created, CarbonImmutable::parse('2026-01-01T10:00:00+00:00'));
        $statusChangedAt = CarbonImmutable::parse('2026-01-01T10:00:00+00:00');
        $appliedOrder = new Order(['external_id' => 'ord_1', 'status' => OrderStatus::Created]);
        MapOrderSnapshotActionMock::returnExecute($snapshot);
        OrderRepositoryMock::returnFind(null, 'ord_1');
        ResolveOrderStatusTimestampActionMock::returnExecute($statusChangedAt);
        OrderRepositoryMock::returnApplySnapshot($appliedOrder, $snapshot);

        $result = app(ApplyOrderEventService::class)->handle([]);

        $this->assertTrue($result->applied);
        $this->assertTrue($result->statusChanged);
        $this->assertNull($result->previousStatus);
        $this->assertSame(OrderStatus::Created, $result->newStatus);
        Event::assertDispatched(
            OrderStatusChanged::class,
            fn (OrderStatusChanged $event) => $event->orderId === 'ord_1'
                && $event->previousStatus === null
                && $event->newStatus === OrderStatus::Created,
        );
    }

    public function test_a_newer_snapshot_that_changes_status_is_applied(): void
    {
        Event::fake();
        $existing = new Order([
            'external_id' => 'ord_1',
            'status' => OrderStatus::Created,
            'updated_at' => CarbonImmutable::parse('2026-01-01T10:00:00+00:00'),
        ]);
        $snapshot = $this->snapshot(OrderStatus::Paid, CarbonImmutable::parse('2026-01-02T10:00:00+00:00'));
        $appliedOrder = new Order(['external_id' => 'ord_1', 'status' => OrderStatus::Paid]);
        MapOrderSnapshotActionMock::returnExecute($snapshot);
        OrderRepositoryMock::returnFind($existing, 'ord_1');
        ResolveOrderStatusTimestampActionMock::returnExecute(CarbonImmutable::now());
        OrderRepositoryMock::returnApplySnapshot($appliedOrder, $snapshot);

        $result = app(ApplyOrderEventService::class)->handle([]);

        $this->assertTrue($result->applied);
        $this->assertTrue($result->statusChanged);
        $this->assertSame(OrderStatus::Created, $result->previousStatus);
        $this->assertSame(OrderStatus::Paid, $result->newStatus);
        Event::assertDispatched(OrderStatusChanged::class);
    }

    public function test_a_newer_snapshot_with_the_same_status_is_applied_without_a_status_change(): void
    {
        Event::fake();
        $existing = new Order([
            'external_id' => 'ord_1',
            'status' => OrderStatus::Created,
            'updated_at' => CarbonImmutable::parse('2026-01-01T10:00:00+00:00'),
        ]);
        $snapshot = $this->snapshot(OrderStatus::Created, CarbonImmutable::parse('2026-01-02T10:00:00+00:00'));
        $appliedOrder = new Order(['external_id' => 'ord_1', 'status' => OrderStatus::Created]);
        MapOrderSnapshotActionMock::returnExecute($snapshot);
        OrderRepositoryMock::returnFind($existing, 'ord_1');
        ResolveOrderStatusTimestampActionMock::returnExecute(CarbonImmutable::now());
        OrderRepositoryMock::returnApplySnapshot($appliedOrder, $snapshot);

        $result = app(ApplyOrderEventService::class)->handle([]);

        $this->assertTrue($result->applied);
        $this->assertFalse($result->statusChanged);
        Event::assertNotDispatched(OrderStatusChanged::class);
    }

    public function test_a_stale_or_replayed_snapshot_is_ignored(): void
    {
        Event::fake();
        $existing = new Order([
            'external_id' => 'ord_1',
            'status' => OrderStatus::Paid,
            'updated_at' => CarbonImmutable::parse('2026-01-02T10:00:00+00:00'),
        ]);
        $staleSnapshot = $this->snapshot(OrderStatus::Created, CarbonImmutable::parse('2026-01-01T10:00:00+00:00'));
        MapOrderSnapshotActionMock::returnExecute($staleSnapshot);
        OrderRepositoryMock::returnFind($existing, 'ord_1');

        $result = app(ApplyOrderEventService::class)->handle([]);

        $this->assertFalse($result->applied);
        $this->assertFalse($result->statusChanged);
        $this->assertSame(OrderStatus::Paid, $result->newStatus);
        Event::assertNotDispatched(OrderStatusChanged::class);
    }

    public function test_a_snapshot_with_the_exact_same_updated_at_is_treated_as_a_replay_and_ignored(): void
    {
        Event::fake();
        $sameInstant = CarbonImmutable::parse('2026-01-01T10:00:00+00:00');
        $existing = new Order([
            'external_id' => 'ord_1',
            'status' => OrderStatus::Created,
            'updated_at' => $sameInstant,
        ]);
        $snapshot = $this->snapshot(OrderStatus::Created, $sameInstant);
        MapOrderSnapshotActionMock::returnExecute($snapshot);
        OrderRepositoryMock::returnFind($existing, 'ord_1');

        $result = app(ApplyOrderEventService::class)->handle([]);

        $this->assertFalse($result->applied);
        Event::assertNotDispatched(OrderStatusChanged::class);
    }
}
