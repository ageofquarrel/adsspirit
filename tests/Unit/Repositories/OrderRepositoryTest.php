<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\DTO\Marketplace\OrderItemSnapshotDTO;
use App\DTO\Marketplace\OrderSnapshotDTO;
use App\DTO\Orders\ListOrdersFiltersDTO;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Repositories\OrderRepository;
use Carbon\CarbonImmutable;
use Tests\TestCase;

final class OrderRepositoryTest extends TestCase
{
    private function snapshot(array $overrides = []): OrderSnapshotDTO
    {
        return new OrderSnapshotDTO(
            externalId: $overrides['externalId'] ?? 'ord_1',
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
        $repository = new OrderRepository;

        $this->assertNull($repository->find('ord_missing'));
    }

    public function test_find_returns_the_stored_order(): void
    {
        $repository = new OrderRepository;
        $repository->applySnapshot($this->snapshot(), CarbonImmutable::now());

        $order = $repository->find('ord_1');

        $this->assertNotNull($order);
        $this->assertSame('ord_1', $order->external_id);
    }

    public function test_apply_snapshot_creates_the_order_with_its_items_and_initial_history(): void
    {
        $repository = new OrderRepository;
        $changedAt = CarbonImmutable::parse('2026-01-01T10:00:00+00:00');

        $order = $repository->applySnapshot($this->snapshot(), $changedAt);

        $this->assertSame('ord_1', $order->external_id);
        $this->assertCount(1, $order->items);
        $this->assertSame('SKU-1', $order->items->first()->sku);
        $this->assertCount(1, $order->statusHistory);
        $this->assertSame(OrderStatus::Created, $order->statusHistory->first()->status);
    }

    public function test_apply_snapshot_replaces_items_on_update(): void
    {
        $repository = new OrderRepository;
        $repository->applySnapshot($this->snapshot(), CarbonImmutable::now());

        $updated = $repository->applySnapshot($this->snapshot([
            'items' => [new OrderItemSnapshotDTO('SKU-2', 'Item 2', 1, '50.00')],
            'updatedAt' => CarbonImmutable::parse('2026-01-02T10:00:00+00:00'),
        ]), CarbonImmutable::now());

        $this->assertCount(1, $updated->items);
        $this->assertSame('SKU-2', $updated->items->first()->sku);
    }

    public function test_apply_snapshot_adds_a_new_history_row_for_a_new_status(): void
    {
        $repository = new OrderRepository;
        $repository->applySnapshot($this->snapshot(), CarbonImmutable::parse('2026-01-01T10:00:00+00:00'));

        $order = $repository->applySnapshot($this->snapshot([
            'status' => OrderStatus::Paid,
            'paidAt' => CarbonImmutable::parse('2026-01-02T10:00:00+00:00'),
            'updatedAt' => CarbonImmutable::parse('2026-01-02T10:00:00+00:00'),
        ]), CarbonImmutable::parse('2026-01-02T10:00:00+00:00'));

        $this->assertCount(2, $order->statusHistory);
        $this->assertSame(
            [OrderStatus::Created, OrderStatus::Paid],
            $order->statusHistory->pluck('status')->all(),
        );
    }

    public function test_apply_snapshot_updates_the_timestamp_when_the_same_status_is_seen_again(): void
    {
        $repository = new OrderRepository;
        $repository->applySnapshot($this->snapshot(), CarbonImmutable::parse('2026-01-01T10:00:00+00:00'));

        $order = $repository->applySnapshot(
            $this->snapshot(['updatedAt' => CarbonImmutable::parse('2026-01-03T10:00:00+00:00')]),
            CarbonImmutable::parse('2026-01-03T09:00:00+00:00'),
        );

        $this->assertCount(1, $order->statusHistory);
        $this->assertTrue(
            CarbonImmutable::parse('2026-01-03T09:00:00+00:00')->eq($order->statusHistory->first()->changed_at),
        );
    }

    public function test_apply_snapshot_is_idempotent_for_the_same_input(): void
    {
        $repository = new OrderRepository;
        $snapshot = $this->snapshot();

        $repository->applySnapshot($snapshot, CarbonImmutable::now());
        $repository->applySnapshot($snapshot, CarbonImmutable::now());

        $this->assertSame(1, Order::query()->count());
    }

    public function test_find_with_details_eager_loads_items_and_history(): void
    {
        $repository = new OrderRepository;
        $repository->applySnapshot($this->snapshot(), CarbonImmutable::now());

        $order = $repository->findWithDetails('ord_1');

        $this->assertTrue($order->relationLoaded('items'));
        $this->assertTrue($order->relationLoaded('statusHistory'));
    }

    public function test_paginate_filters_by_status(): void
    {
        $repository = new OrderRepository;
        $repository->applySnapshot($this->snapshot(['externalId' => 'ord_1']), CarbonImmutable::now());
        $repository->applySnapshot(
            $this->snapshot(['externalId' => 'ord_2', 'number' => 'MH-2', 'status' => OrderStatus::Paid, 'paidAt' => CarbonImmutable::now()]),
            CarbonImmutable::now(),
        );

        $result = $repository->paginate(new ListOrdersFiltersDTO(OrderStatus::Paid, null, 1, 20));

        $this->assertSame(1, $result->total());
        $this->assertSame('ord_2', $result->items()[0]->external_id);
    }

    public function test_paginate_filters_by_email_case_insensitively(): void
    {
        $repository = new OrderRepository;
        Order::factory()->create(['external_id' => 'ord_1', 'customer_email' => 'Anna@Example.com']);
        Order::factory()->create(['external_id' => 'ord_2', 'customer_email' => 'other@example.com']);

        $result = $repository->paginate(new ListOrdersFiltersDTO(null, 'anna@example.com', 1, 20));

        $this->assertSame(1, $result->total());
        $this->assertSame('ord_1', $result->items()[0]->external_id);
    }

    public function test_paginate_orders_by_created_at_descending(): void
    {
        $repository = new OrderRepository;
        Order::factory()->create(['external_id' => 'ord_old', 'created_at' => CarbonImmutable::parse('2026-01-01T00:00:00+00:00')]);
        Order::factory()->create(['external_id' => 'ord_new', 'created_at' => CarbonImmutable::parse('2026-01-02T00:00:00+00:00')]);

        $result = $repository->paginate(new ListOrdersFiltersDTO(null, null, 1, 20));

        $this->assertSame(['ord_new', 'ord_old'], collect($result->items())->pluck('external_id')->all());
    }

    public function test_paginate_respects_page_and_per_page(): void
    {
        $repository = new OrderRepository;
        Order::factory()->count(3)->create();

        $result = $repository->paginate(new ListOrdersFiltersDTO(null, null, 2, 2));

        $this->assertSame(3, $result->total());
        $this->assertSame(2, $result->currentPage());
        $this->assertCount(1, $result->items());
    }
}
