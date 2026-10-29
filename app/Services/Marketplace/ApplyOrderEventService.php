<?php

namespace App\Services\Marketplace;

use App\Actions\Marketplace\MapOrderSnapshotAction;
use App\Actions\Marketplace\ResolveOrderStatusTimestampAction;
use App\DTO\Marketplace\ApplyOrderEventResultDTO;
use App\Repositories\Contracts\OrderRepositoryInterface;

final readonly class ApplyOrderEventService
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private MapOrderSnapshotAction $mapSnapshot,
        private ResolveOrderStatusTimestampAction $resolveStatusTimestamp,
    ) {}

    /**
     * @param  array<string, mixed>  $rawOrder  The `data.order` snapshot from the webhook body.
     */
    public function handle(array $rawOrder): ApplyOrderEventResultDTO
    {
        $snapshot = ($this->mapSnapshot)($rawOrder);
        $existing = $this->repository->find($snapshot->id);

        // Out-of-order / replayed delivery guard: only strictly newer snapshots are applied.
        if ($existing !== null && $existing->updated_at->greaterThanOrEqualTo($snapshot->updatedAt)) {
            return new ApplyOrderEventResultDTO(
                applied: false,
                statusChanged: false,
                previousStatus: $existing->status,
                newStatus: $existing->status,
                order: $existing,
            );
        }

        $previousStatus = $existing?->status;
        $statusChangedAt = ($this->resolveStatusTimestamp)($snapshot);
        $order = $this->repository->applySnapshot($snapshot, $statusChangedAt);

        return new ApplyOrderEventResultDTO(
            applied: true,
            statusChanged: $previousStatus !== $order->status,
            previousStatus: $previousStatus,
            newStatus: $order->status,
            order: $order,
        );
    }
}
