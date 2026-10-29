<?php

namespace App\Repositories\Contracts;

use App\DTO\Marketplace\OrderSnapshotDTO;
use App\DTO\Orders\ListOrdersFiltersDTO;
use App\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    public function find(string $id): ?Order;

    /**
     * Same as {@see self::find()}, but eager-loads the relations needed for
     * the order detail view (items, status history).
     */
    public function findWithDetails(string $id): ?Order;

    public function paginate(ListOrdersFiltersDTO $filters): LengthAwarePaginator;

    /**
     * Upserts the order, fully replaces its items, and records the status
     * transition in the status history (upserted by status, so a replayed
     * snapshot for an already-recorded status just refreshes its timestamp).
     */
    public function applySnapshot(OrderSnapshotDTO $data, CarbonInterface $statusChangedAt): Order;
}
