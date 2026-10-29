<?php

namespace Tests\Doubles\Actions;

use App\Actions\Marketplace\ResolveOrderStatusTimestampAction;
use App\DTO\Marketplace\OrderSnapshotDTO;
use Carbon\CarbonImmutable;

/**
 * Test double for {@see ResolveOrderStatusTimestampAction}, which is already
 * covered by its own test.
 */
final class ResolveOrderStatusTimestampActionMock extends ResolveOrderStatusTimestampAction
{
    public function __construct(private readonly CarbonImmutable $returnValue) {}

    public function __invoke(OrderSnapshotDTO $snapshot): CarbonImmutable
    {
        return $this->returnValue;
    }
}
