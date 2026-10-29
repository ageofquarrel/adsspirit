<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Services\Notifications\SendOrderStatusEmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SendOrderStatusEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $orderId,
        public readonly OrderStatus $type,
    ) {}

    public function handle(SendOrderStatusEmailService $service): void
    {
        $service->handle($this->orderId, $this->type);
    }
}
