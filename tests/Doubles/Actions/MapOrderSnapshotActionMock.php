<?php

namespace Tests\Doubles\Actions;

use App\Actions\Marketplace\MapOrderSnapshotAction;
use App\DTO\Marketplace\OrderSnapshotDTO;

/**
 * Test double for {@see MapOrderSnapshotAction}, which is already covered by
 * its own test. Returns a fixed snapshot regardless of the raw input, so
 * service-level tests can control exactly which snapshot flows downstream.
 */
final class MapOrderSnapshotActionMock extends MapOrderSnapshotAction
{
    public function __construct(private readonly OrderSnapshotDTO $returnValue) {}

    /**
     * @param  array<string, mixed>  $order
     */
    public function __invoke(array $order): OrderSnapshotDTO
    {
        return $this->returnValue;
    }
}
