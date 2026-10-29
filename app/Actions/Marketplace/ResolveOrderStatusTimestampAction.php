<?php

namespace App\Actions\Marketplace;

use App\DTO\Marketplace\OrderSnapshotDTO;
use App\Enums\OrderStatus;
use Carbon\CarbonImmutable;

/**
 * MarketHub does not send explicit `shipped_at`/`delivered_at` fields, so the
 * moment a status was reached is approximated from whatever timestamp the
 * snapshot does carry for that status (see DECISIONS.md).
 */
class ResolveOrderStatusTimestampAction
{
    public function __invoke(OrderSnapshotDTO $snapshot): CarbonImmutable
    {
        return match ($snapshot->status) {
            OrderStatus::Created => $snapshot->createdAt,
            OrderStatus::Paid => $snapshot->paidAt ?? $snapshot->updatedAt,
            OrderStatus::Cancelled => $snapshot->cancelledAt ?? $snapshot->updatedAt,
            OrderStatus::Shipped, OrderStatus::Delivered => $snapshot->updatedAt,
        };
    }
}
