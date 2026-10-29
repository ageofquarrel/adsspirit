<?php

declare(strict_types=1);

namespace Tests\Mocks\Internal\App\Services\Notifications;

use App\Enums\OrderStatus;
use App\Services\Notifications\SendOrderStatusEmailService;
use Mockery;
use Tests\Mocks\Internal\BaseServiceMock;

/**
 * Мок сервиса SendOrderStatusEmailService.
 */
final class SendOrderStatusEmailServiceMock extends BaseServiceMock
{
    /**
     * Регистрирует мок без ожиданий — для случаев, когда handle() не должен вызываться.
     */
    public static function register(): void
    {
        self::mock(SendOrderStatusEmailService::class, Mockery::mock(SendOrderStatusEmailService::class));
    }

    /**
     * Ожидает вызов handle() с заданными аргументами.
     */
    public static function expectHandle(string $orderId, OrderStatus $type, int $times = 1): void
    {
        $mock = Mockery::mock(SendOrderStatusEmailService::class);
        $mock->shouldReceive('handle')
            ->with($orderId, $type)
            ->times($times);
        self::mock(SendOrderStatusEmailService::class, $mock);
    }
}
