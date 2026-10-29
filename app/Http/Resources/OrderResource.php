<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
final class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->external_id,
            'number' => $this->number,
            'status' => $this->status->value,
            'customer' => [
                'email' => $this->customer_email,
                'name' => $this->customer_name,
                'phone' => $this->customer_phone,
            ],
            'currency' => $this->currency,
            'items' => OrderItemResource::collection($this->items),
            'delivery_price' => $this->delivery_price,
            'discount' => $this->discount,
            'total' => $this->total,
            'shipment' => $this->shipment_carrier === null ? null : [
                'carrier' => $this->shipment_carrier,
                'tracking_number' => $this->shipment_tracking_number,
                'tracking_url' => $this->shipment_tracking_url,
            ],
            'paid_at' => $this->paid_at?->toAtomString(),
            'cancelled_at' => $this->cancelled_at?->toAtomString(),
            'cancel_reason' => $this->cancel_reason,
            'created_at' => $this->created_at->toAtomString(),
            'updated_at' => $this->updated_at->toAtomString(),
            'history' => OrderStatusHistoryResource::collection($this->statusHistory),
        ];
    }
}
