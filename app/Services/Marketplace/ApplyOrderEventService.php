<?php

declare(strict_types=1);

namespace App\Services\Marketplace;

use App\Actions\Marketplace\MapOrderSnapshotAction;
use App\Actions\Marketplace\ResolveOrderStatusTimestampAction;
use App\DTO\Marketplace\ApplyOrderEventResultDTO;
use App\Events\OrderStatusChanged;
use App\Repositories\Contracts\OrderRepositoryInterface;

final readonly class ApplyOrderEventService
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private MapOrderSnapshotAction $mapSnapshot,
        private ResolveOrderStatusTimestampAction $resolveStatusTimestamp,
    ) {}

    /**
     * @param  array<string, mixed>  $rawOrder  Снэпшот `data.order` из тела вебхука.
     */
    public function handle(array $rawOrder): ApplyOrderEventResultDTO
    {
        $snapshot = $this->mapSnapshot->execute($rawOrder);
        $existing = $this->repository->find($snapshot->externalId);

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
        $statusChangedAt = $this->resolveStatusTimestamp->execute($snapshot);
        $order = $this->repository->applySnapshot($snapshot, $statusChangedAt);
        $statusChanged = $previousStatus !== $order->status;

        if ($statusChanged) {
            OrderStatusChanged::dispatch($order->external_id, $previousStatus, $order->status);
        }

        return new ApplyOrderEventResultDTO(
            applied: true,
            statusChanged: $statusChanged,
            previousStatus: $previousStatus,
            newStatus: $order->status,
            order: $order,
        );
    }
}
