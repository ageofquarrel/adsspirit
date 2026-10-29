<?php

declare(strict_types=1);

namespace Tests\Mocks\Internal\App\Repositories\Contracts;

use App\Repositories\Contracts\OrderNotificationRepositoryInterface;
use Mockery;
use Tests\Mocks\Internal\BaseServiceMock;

/**
 * Мок репозитория OrderNotificationRepository.
 */
final class OrderNotificationRepositoryMock extends BaseServiceMock
{
    /**
     * Возвращает результат резервирования уведомления из reserve().
     */
    public static function returnReserve(bool $result, int $times = 1): void
    {
        $mock = Mockery::mock(OrderNotificationRepositoryInterface::class);
        $mock->shouldReceive('reserve')
            ->andReturn($result)
            ->times($times);
        self::mock(OrderNotificationRepositoryInterface::class, $mock);
    }
}
