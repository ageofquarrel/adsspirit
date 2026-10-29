<?php

namespace Tests\Unit\Services\Orders;

use App\Models\Order;
use App\Services\Orders\GetOrderService;
use Tests\Doubles\Repositories\OrderRepositoryMock;
use Tests\TestCase;

class GetOrderServiceTest extends TestCase
{
    public function test_it_returns_the_order_from_the_repository(): void
    {
        // Arrange
        $repository = new OrderRepositoryMock;
        $repository->findWithDetailsReturn = new Order(['id' => 'ord_1']);
        $service = new GetOrderService($repository);

        // Act
        $order = $service->handle('ord_1');

        // Assert
        $this->assertSame($repository->findWithDetailsReturn, $order);
    }

    public function test_it_returns_null_when_the_repository_finds_nothing(): void
    {
        // Arrange
        $repository = new OrderRepositoryMock;
        $repository->findWithDetailsReturn = null;
        $service = new GetOrderService($repository);

        // Act
        $order = $service->handle('ord_missing');

        // Assert
        $this->assertNull($order);
    }
}
