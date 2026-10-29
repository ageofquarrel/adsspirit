<?php

declare(strict_types=1);

namespace Tests\Mocks\Internal\App\Actions\Marketplace;

use App\Actions\Marketplace\ResolveOrderStatusTimestampAction;
use Carbon\CarbonImmutable;
use Mockery;
use Tests\Mocks\Internal\BaseServiceMock;

/**
 * Мок экшена ResolveOrderStatusTimestampAction.
 */
final class ResolveOrderStatusTimestampActionMock extends BaseServiceMock
{
    /**
     * Возвращает фиксированную метку времени из execute().
     */
    public static function returnExecute(CarbonImmutable $statusChangedAt, int $times = 1): void
    {
        $mock = Mockery::mock(ResolveOrderStatusTimestampAction::class);
        $mock->shouldReceive('execute')
            ->andReturn($statusChangedAt)
            ->times($times);
        self::mock(ResolveOrderStatusTimestampAction::class, $mock);
    }
}
