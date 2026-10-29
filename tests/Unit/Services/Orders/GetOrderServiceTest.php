<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Orders;

use App\Models\Order;
use App\Services\Orders\GetOrderService;
use Tests\Mocks\Internal\App\Repositories\Contracts\OrderRepositoryMock;
use Tests\TestCase;

final class GetOrderServiceTest extends TestCase
{
    public function test_it_returns_the_order_from_the_repository(): void
    {
        $order = new Order(['external_id' => 'ord_1']);
        OrderRepositoryMock::returnFindWithDetails($order);

        $result = app(GetOrderService::class)->handle('ord_1');

        $this->assertSame($order, $result);
    }

    public function test_it_returns_null_when_the_repository_finds_nothing(): void
    {
        OrderRepositoryMock::returnFindWithDetails(null);

        $result = app(GetOrderService::class)->handle('ord_missing');

        $this->assertNull($result);
    }
}
