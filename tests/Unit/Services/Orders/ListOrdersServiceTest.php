<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Orders;

use App\DTO\Orders\ListOrdersFiltersDTO;
use App\Services\Orders\ListOrdersService;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\Mocks\Internal\App\Repositories\Contracts\OrderRepositoryMock;
use Tests\TestCase;

final class ListOrdersServiceTest extends TestCase
{
    public function test_it_delegates_to_the_repository_and_returns_its_result(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 20, 1);
        OrderRepositoryMock::returnPaginate($paginator);
        $filters = new ListOrdersFiltersDTO(null, null, 1, 20);

        $result = app(ListOrdersService::class)->handle($filters);

        $this->assertSame($paginator, $result);
    }
}
