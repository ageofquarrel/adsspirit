<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;

final readonly class GetOrderService
{
    public function __construct(
        private OrderRepositoryInterface $repository,
    ) {}

    public function handle(string $id): ?Order
    {
        return $this->repository->findWithDetails($id);
    }
}
