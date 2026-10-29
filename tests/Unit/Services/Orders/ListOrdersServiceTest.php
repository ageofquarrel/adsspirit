<?php

namespace Tests\Unit\Services\Orders;

use App\DTO\Orders\ListOrdersFiltersDTO;
use App\Services\Orders\ListOrdersService;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\Doubles\Repositories\OrderRepositoryMock;
use Tests\TestCase;

class ListOrdersServiceTest extends TestCase
{
    public function test_it_delegates_to_the_repository_and_returns_its_result(): void
    {
        // Arrange
        $repository = new OrderRepositoryMock;
        $paginator = new LengthAwarePaginator([], 0, 20, 1);
        $repository->paginateReturn = $paginator;
        $service = new ListOrdersService($repository);
        $filters = new ListOrdersFiltersDTO(null, null, 1, 20);

        // Act
        $result = $service->handle($filters);

        // Assert
        $this->assertSame($paginator, $result);
        $this->assertSame($filters, $repository->lastFilters);
    }
}
