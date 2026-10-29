<?php

declare(strict_types=1);

namespace Tests\Mocks\Internal\App\Repositories\Contracts;

use App\DTO\Marketplace\ReceiveWebhookEventDTO;
use App\Models\MarketplaceWebhookEvent;
use App\Repositories\Contracts\MarketplaceWebhookEventRepositoryInterface;
use Mockery;
use Mockery\MockInterface;
use Tests\Mocks\Internal\BaseServiceMock;

/**
 * Мок репозитория MarketplaceWebhookEventRepository.
 */
final class MarketplaceWebhookEventRepositoryMock extends BaseServiceMock
{
    /**
     * Возвращает сохранённое событие (или null) из findByEventId().
     */
    public static function returnFindByEventId(?MarketplaceWebhookEvent $event, int $times = 1): void
    {
        $mock = self::resolve();
        $mock->shouldReceive('findByEventId')
            ->andReturn($event)
            ->times($times);
        self::bind($mock);
    }

    /**
     * Возвращает созданное событие из create(), проверяя, что в репозиторий
     * попали именно ожидаемые данные.
     */
    public static function returnCreate(MarketplaceWebhookEvent $event, ReceiveWebhookEventDTO $data, int $times = 1): void
    {
        $mock = self::resolve();
        $mock->shouldReceive('create')
            ->withArgs(static fn (ReceiveWebhookEventDTO $arg) => $arg === $data)
            ->andReturn($event)
            ->times($times);
        self::bind($mock);
    }

    private static function resolve(): MockInterface
    {
        return self::existing(MarketplaceWebhookEventRepositoryInterface::class)
            ?? Mockery::mock(MarketplaceWebhookEventRepositoryInterface::class);
    }

    private static function bind(MockInterface $mock): void
    {
        self::mock(MarketplaceWebhookEventRepositoryInterface::class, $mock);
    }
}
