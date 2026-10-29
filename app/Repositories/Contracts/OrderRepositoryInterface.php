<?php

namespace App\Repositories\Contracts;

use App\DTO\Marketplace\OrderSnapshotDTO;
use App\Models\Order;
use Carbon\CarbonInterface;

interface OrderRepositoryInterface
{
    public function find(string $id): ?Order;

    /**
     * Upserts the order, fully replaces its items, and records the status
     * transition in the status history (upserted by status, so a replayed
     * snapshot for an already-recorded status just refreshes its timestamp).
     */
    public function applySnapshot(OrderSnapshotDTO $data, CarbonInterface $statusChangedAt): Order;
}
