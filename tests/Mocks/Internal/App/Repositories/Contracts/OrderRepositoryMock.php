<?php

declare(strict_types=1);

namespace Tests\Mocks\Internal\App\Repositories\Contracts;

use App\DTO\Marketplace\OrderSnapshotDTO;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\MockInterface;
use Tests\Mocks\Internal\BaseServiceMock;

/**
 * Мок репозитория OrderRepository.
 */
final class OrderRepositoryMock extends BaseServiceMock
{
    /**
     * Возвращает заказ (или null) из find().
     */
    public static function returnFind(?Order $order, string $externalId, int $times = 1): void
    {
        $mock = self::resolve();
        $mock->shouldReceive('find')
            ->with($externalId)
            ->andReturn($order)
            ->times($times);
        self::bind($mock);
    }

    /**
     * Возвращает заказ (или null) из findWithDetails().
     */
    public static function returnFindWithDetails(?Order $order, int $times = 1): void
    {
        $mock = self::resolve();
        $mock->shouldReceive('findWithDetails')
            ->andReturn($order)
            ->times($times);
        self::bind($mock);
    }

    /**
     * Возвращает страницу заказов из paginate().
     */
    public static function returnPaginate(LengthAwarePaginator $paginator, int $times = 1): void
    {
        $mock = self::resolve();
        $mock->shouldReceive('paginate')
            ->andReturn($paginator)
            ->times($times);
        self::bind($mock);
    }

    /**
     * Возвращает заказ из applySnapshot(), проверяя, что в репозиторий
     * попал именно ожидаемый снэпшот.
     */
    public static function returnApplySnapshot(Order $order, OrderSnapshotDTO $snapshot, int $times = 1): void
    {
        $mock = self::resolve();
        $mock->shouldReceive('applySnapshot')
            ->withArgs(static fn (OrderSnapshotDTO $arg, $statusChangedAt) => $arg === $snapshot)
            ->andReturn($order)
            ->times($times);
        self::bind($mock);
    }

    private static function resolve(): MockInterface
    {
        return self::existing(OrderRepositoryInterface::class) ?? Mockery::mock(OrderRepositoryInterface::class);
    }

    private static function bind(MockInterface $mock): void
    {
        self::mock(OrderRepositoryInterface::class, $mock);
    }
}
