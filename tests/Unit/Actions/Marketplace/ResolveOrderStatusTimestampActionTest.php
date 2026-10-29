<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Marketplace;

use App\Actions\Marketplace\ResolveOrderStatusTimestampAction;
use App\DTO\Marketplace\OrderSnapshotDTO;
use App\Enums\OrderStatus;
use Carbon\CarbonImmutable;
use Tests\TestCase;

final class ResolveOrderStatusTimestampActionTest extends TestCase
{
    private function snapshot(OrderStatus $status, ?CarbonImmutable $paidAt, ?CarbonImmutable $cancelledAt): OrderSnapshotDTO
    {
        return new OrderSnapshotDTO(
            externalId: 'ord_1',
            number: 'MH-1',
            status: $status,
            currency: 'RUB',
            customerEmail: 'a@example.com',
            customerName: 'A',
            customerPhone: null,
            items: [],
            deliveryPrice: '0.00',
            discount: '0.00',
            total: '0.00',
            shipmentCarrier: null,
            shipmentTrackingNumber: null,
            shipmentTrackingUrl: null,
            paidAt: $paidAt,
            cancelledAt: $cancelledAt,
            cancelReason: null,
            createdAt: CarbonImmutable::parse('2026-01-01T10:00:00+00:00'),
            updatedAt: CarbonImmutable::parse('2026-01-02T10:00:00+00:00'),
        );
    }

    public function test_created_uses_created_at(): void
    {
        $action = new ResolveOrderStatusTimestampAction;
        $snapshot = $this->snapshot(OrderStatus::Created, null, null);

        $this->assertTrue($snapshot->createdAt->eq($action->execute($snapshot)));
    }

    public function test_paid_uses_paid_at_when_present(): void
    {
        $action = new ResolveOrderStatusTimestampAction;
        $paidAt = CarbonImmutable::parse('2026-01-01T12:00:00+00:00');
        $snapshot = $this->snapshot(OrderStatus::Paid, $paidAt, null);

        $this->assertTrue($paidAt->eq($action->execute($snapshot)));
    }

    public function test_paid_falls_back_to_updated_at_when_paid_at_is_missing(): void
    {
        $action = new ResolveOrderStatusTimestampAction;
        $snapshot = $this->snapshot(OrderStatus::Paid, null, null);

        $this->assertTrue($snapshot->updatedAt->eq($action->execute($snapshot)));
    }

    public function test_cancelled_uses_cancelled_at_when_present(): void
    {
        $action = new ResolveOrderStatusTimestampAction;
        $cancelledAt = CarbonImmutable::parse('2026-01-01T15:00:00+00:00');
        $snapshot = $this->snapshot(OrderStatus::Cancelled, null, $cancelledAt);

        $this->assertTrue($cancelledAt->eq($action->execute($snapshot)));
    }

    public function test_cancelled_falls_back_to_updated_at_when_cancelled_at_is_missing(): void
    {
        $action = new ResolveOrderStatusTimestampAction;
        $snapshot = $this->snapshot(OrderStatus::Cancelled, null, null);

        $this->assertTrue($snapshot->updatedAt->eq($action->execute($snapshot)));
    }

    public function test_shipped_and_delivered_use_updated_at(): void
    {
        $action = new ResolveOrderStatusTimestampAction;

        $this->assertTrue($this->snapshot(OrderStatus::Shipped, null, null)->updatedAt->eq(
            $action->execute($this->snapshot(OrderStatus::Shipped, null, null)),
        ));
        $this->assertTrue($this->snapshot(OrderStatus::Delivered, null, null)->updatedAt->eq(
            $action->execute($this->snapshot(OrderStatus::Delivered, null, null)),
        ));
    }
}
