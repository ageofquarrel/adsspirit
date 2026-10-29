<?php

declare(strict_types=1);

namespace App\Actions\Marketplace;

use App\DTO\Marketplace\OrderSnapshotDTO;
use App\Enums\OrderStatus;
use Carbon\CarbonImmutable;

/**
 * MarketHub не присылает явные поля `shipped_at`/`delivered_at`, поэтому
 * момент перехода в статус приближённо берётся из того таймстемпа, который
 * снэпшот всё же несёт для этого статуса (см. DECISIONS.md).
 */
class ResolveOrderStatusTimestampAction
{
    public function execute(OrderSnapshotDTO $snapshot): CarbonImmutable
    {
        return match ($snapshot->status) {
            OrderStatus::Created => $snapshot->createdAt,
            OrderStatus::Paid => $snapshot->paidAt ?? $snapshot->updatedAt,
            OrderStatus::Cancelled => $snapshot->cancelledAt ?? $snapshot->updatedAt,
            OrderStatus::Shipped, OrderStatus::Delivered => $snapshot->updatedAt,
        };
    }
}
