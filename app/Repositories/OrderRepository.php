<?php

namespace App\Repositories;

use App\DTO\Marketplace\OrderSnapshotDTO;
use App\DTO\Orders\ListOrdersFiltersDTO;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class OrderRepository implements OrderRepositoryInterface
{
    public function find(string $id): ?Order
    {
        return Order::query()->find($id);
    }

    public function findWithDetails(string $id): ?Order
    {
        return Order::query()->with(['items', 'statusHistory'])->find($id);
    }

    public function paginate(ListOrdersFiltersDTO $filters): LengthAwarePaginator
    {
        return Order::query()
            ->when($filters->status, fn ($query, $status) => $query->where('status', $status))
            ->when(
                $filters->email,
                fn ($query, $email) => $query->whereRaw('LOWER(customer_email) = ?', [Str::lower($email)]),
            )
            ->orderByDesc('created_at')
            ->paginate(perPage: $filters->perPage, page: $filters->page);
    }

    public function applySnapshot(OrderSnapshotDTO $data, CarbonInterface $statusChangedAt): Order
    {
        return DB::transaction(function () use ($data, $statusChangedAt) {
            $order = Order::query()->updateOrCreate(['id' => $data->id], [
                'number' => $data->number,
                'status' => $data->status,
                'currency' => $data->currency,
                'customer_email' => $data->customerEmail,
                'customer_name' => $data->customerName,
                'customer_phone' => $data->customerPhone,
                'delivery_price' => $data->deliveryPrice,
                'discount' => $data->discount,
                'total' => $data->total,
                'shipment_carrier' => $data->shipmentCarrier,
                'shipment_tracking_number' => $data->shipmentTrackingNumber,
                'shipment_tracking_url' => $data->shipmentTrackingUrl,
                'paid_at' => $data->paidAt,
                'cancelled_at' => $data->cancelledAt,
                'cancel_reason' => $data->cancelReason,
                'created_at' => $data->createdAt,
                'updated_at' => $data->updatedAt,
            ]);

            $order->items()->delete();

            foreach ($data->items as $item) {
                $order->items()->create([
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ]);
            }

            $order->statusHistory()->updateOrCreate(
                ['status' => $data->status],
                ['changed_at' => $statusChangedAt],
            );

            return $order->fresh(['items', 'statusHistory']);
        });
    }
}
