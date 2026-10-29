<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\DTO\Orders\ListOrdersFiltersDTO;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class ListOrdersService
{
    public function __construct(
        private OrderRepositoryInterface $repository,
    ) {}

    public function handle(ListOrdersFiltersDTO $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }
}
