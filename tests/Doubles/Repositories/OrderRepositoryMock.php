<?php

namespace Tests\Doubles\Repositories;

use App\DTO\Marketplace\OrderSnapshotDTO;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\OrderRepository;
use Carbon\CarbonInterface;

/**
 * Test double for {@see OrderRepository}, which is already
 * covered by its own repository test. Used in service-level tests so they
 * don't have to hit the database again.
 */
final class OrderRepositoryMock implements OrderRepositoryInterface
{
    public ?Order $findReturn = null;

    public ?Order $applySnapshotReturn = null;

    public int $applySnapshotCallCount = 0;

    public ?OrderSnapshotDTO $lastSnapshot = null;

    public ?CarbonInterface $lastStatusChangedAt = null;

    public function find(string $id): ?Order
    {
        return $this->findReturn;
    }

    public function applySnapshot(OrderSnapshotDTO $data, CarbonInterface $statusChangedAt): Order
    {
        $this->applySnapshotCallCount++;
        $this->lastSnapshot = $data;
        $this->lastStatusChangedAt = $statusChangedAt;

        return $this->applySnapshotReturn ?? new Order([
            'id' => $data->id,
            'number' => $data->number,
            'status' => $data->status,
            'currency' => $data->currency,
            'customer_email' => $data->customerEmail,
            'customer_name' => $data->customerName,
            'customer_phone' => $data->customerPhone,
            'delivery_price' => $data->deliveryPrice,
            'discount' => $data->discount,
            'total' => $data->total,
            'paid_at' => $data->paidAt,
            'cancelled_at' => $data->cancelledAt,
            'cancel_reason' => $data->cancelReason,
            'created_at' => $data->createdAt,
            'updated_at' => $data->updatedAt,
        ]);
    }
}
