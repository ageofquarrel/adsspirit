<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTO\Marketplace\OrderSnapshotDTO;
use App\DTO\Orders\ListOrdersFiltersDTO;
use App\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    public function find(string $externalId): ?Order;

    public function findWithDetails(string $externalId): ?Order;

    public function paginate(ListOrdersFiltersDTO $filters): LengthAwarePaginator;

    /**
     * Создаёт или обновляет заказ, полностью пересоздаёт его позиции
     * и фиксирует переход статуса в истории.
     */
    public function applySnapshot(OrderSnapshotDTO $data, CarbonInterface $statusChangedAt): Order;
}
