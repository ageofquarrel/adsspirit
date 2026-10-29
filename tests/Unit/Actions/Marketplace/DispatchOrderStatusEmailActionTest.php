<?php

namespace Tests\Unit\Actions\Marketplace;

use App\Actions\Marketplace\DispatchOrderStatusEmailAction;
use App\DTO\Marketplace\ApplyOrderEventResultDTO;
use App\Enums\OrderStatus;
use App\Jobs\SendOrderStatusEmailJob;
use App\Models\Order;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DispatchOrderStatusEmailActionTest extends TestCase
{
    private function applyResult(bool $statusChanged, OrderStatus $newStatus): ApplyOrderEventResultDTO
    {
        return new ApplyOrderEventResultDTO(
            applied: true,
            statusChanged: $statusChanged,
            previousStatus: null,
            newStatus: $newStatus,
            order: new Order(['id' => 'ord_1', 'status' => $newStatus]),
        );
    }

    /**
     * @return array<string, array{0: bool, 1: OrderStatus, 2: bool}>
     */
    public static function cases(): array
    {
        return [
            'paid transition dispatches' => [true, OrderStatus::Paid, true],
            'shipped transition dispatches' => [true, OrderStatus::Shipped, true],
            'cancelled transition dispatches' => [true, OrderStatus::Cancelled, true],
            'created transition does not dispatch' => [true, OrderStatus::Created, false],
            'delivered transition does not dispatch' => [true, OrderStatus::Delivered, false],
            'unchanged paid status does not dispatch' => [false, OrderStatus::Paid, false],
        ];
    }

    #[DataProvider('cases')]
    public function test_it_dispatches_only_for_real_transitions_into_notifiable_statuses(
        bool $statusChanged,
        OrderStatus $newStatus,
        bool $shouldDispatch,
    ): void {
        // Arrange
        Bus::fake();
        $action = new DispatchOrderStatusEmailAction;

        // Act
        $action($this->applyResult($statusChanged, $newStatus));

        // Assert
        if ($shouldDispatch) {
            Bus::assertDispatched(
                SendOrderStatusEmailJob::class,
                fn (SendOrderStatusEmailJob $job) => $job->orderId === 'ord_1' && $job->type === $newStatus,
            );
        } else {
            Bus::assertNotDispatched(SendOrderStatusEmailJob::class);
        }
    }
}
