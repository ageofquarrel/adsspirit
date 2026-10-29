<?php

declare(strict_types=1);

namespace Tests\Mocks\Internal\App\Actions\Marketplace;

use App\Actions\Marketplace\MapOrderSnapshotAction;
use App\DTO\Marketplace\OrderSnapshotDTO;
use Mockery;
use Tests\Mocks\Internal\BaseServiceMock;

/**
 * Мок экшена MapOrderSnapshotAction.
 */
final class MapOrderSnapshotActionMock extends BaseServiceMock
{
    /**
     * Возвращает фиксированный снэпшот из execute() независимо от входных данных.
     */
    public static function returnExecute(OrderSnapshotDTO $snapshot, int $times = 1): void
    {
        $mock = Mockery::mock(MapOrderSnapshotAction::class);
        $mock->shouldReceive('execute')
            ->andReturn($snapshot)
            ->times($times);
        self::mock(MapOrderSnapshotAction::class, $mock);
    }
}
