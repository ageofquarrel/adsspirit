<?php

declare(strict_types=1);

namespace App\DTO\Orders;

use App\Enums\OrderStatus;

final readonly class ListOrdersFiltersDTO
{
    public function __construct(
        public ?OrderStatus $status,
        public ?string $email,
        public int $page,
        public int $perPage,
    ) {}
}
