<?php

namespace App\Actions\Marketplace;

use App\DTO\Marketplace\ApplyOrderEventResultDTO;
use App\Enums\OrderStatus;
use App\Jobs\SendOrderStatusEmailJob;

/**
 * Queues the customer-facing email for a status transition. Only transitions
 * (not replays/no-ops) into a notifiable status trigger an email — the job
 * itself re-validates and de-duplicates when it actually runs.
 */
class DispatchOrderStatusEmailAction
{
    private const array NOTIFIABLE_STATUSES = [
        OrderStatus::Paid,
        OrderStatus::Shipped,
        OrderStatus::Cancelled,
    ];

    public function __invoke(ApplyOrderEventResultDTO $result): void
    {
        if (! $result->statusChanged) {
            return;
        }

        if (! in_array($result->newStatus, self::NOTIFIABLE_STATUSES, true)) {
            return;
        }

        SendOrderStatusEmailJob::dispatch($result->order->id, $result->newStatus);
    }
}
