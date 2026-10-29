<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\DTO\Orders\ListOrdersFiltersDTO;
use App\Enums\OrderStatus;
use App\Http\Requests\Orders\ListOrdersRequest;

/**
 * Преобразует провалидированный массив из {@see ListOrdersRequest}
 * в типизированный {@see ListOrdersFiltersDTO} с дефолтами по странице и размеру страницы.
 */
final class MapListOrdersFiltersAction
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(array $validated): ListOrdersFiltersDTO
    {
        $status = $validated['status'] ?? null;

        return new ListOrdersFiltersDTO(
            status: $status !== null ? OrderStatus::from($status) : null,
            email: $validated['email'] ?? null,
            page: (int) ($validated['page'] ?? 1),
            perPage: (int) ($validated['per_page'] ?? 20),
        );
    }
}
