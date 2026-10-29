<?php

namespace App\Actions\Marketplace;

use App\DTO\Marketplace\OrderItemSnapshotDTO;
use App\DTO\Marketplace\OrderSnapshotDTO;
use App\Enums\OrderStatus;
use App\Http\Requests\Marketplace\StoreMarketplaceWebhookRequest;
use Carbon\CarbonImmutable;

/**
 * Maps the raw `data.order` array from a marketplace webhook body into
 * a typed {@see OrderSnapshotDTO}. Assumes the shape has already been
 * validated by {@see StoreMarketplaceWebhookRequest}.
 */
class MapOrderSnapshotAction
{
    /**
     * @param  array<string, mixed>  $order
     */
    public function __invoke(array $order): OrderSnapshotDTO
    {
        $customer = $order['customer'];
        $shipment = $order['shipment'] ?? null;

        return new OrderSnapshotDTO(
            id: $order['id'],
            number: $order['number'],
            status: OrderStatus::from($order['status']),
            currency: $order['currency'],
            customerEmail: $customer['email'],
            customerName: $customer['name'],
            customerPhone: $customer['phone'] ?? null,
            items: array_map(
                fn (array $item) => new OrderItemSnapshotDTO(
                    sku: $item['sku'],
                    name: $item['name'],
                    quantity: (int) $item['quantity'],
                    price: (string) $item['price'],
                ),
                $order['items'],
            ),
            deliveryPrice: (string) $order['delivery_price'],
            discount: (string) $order['discount'],
            total: (string) $order['total'],
            shipmentCarrier: $shipment['carrier'] ?? null,
            shipmentTrackingNumber: $shipment['tracking_number'] ?? null,
            shipmentTrackingUrl: $shipment['tracking_url'] ?? null,
            paidAt: $this->parseNullableDate($order['paid_at'] ?? null),
            cancelledAt: $this->parseNullableDate($order['cancelled_at'] ?? null),
            cancelReason: $order['cancel_reason'] ?? null,
            createdAt: CarbonImmutable::parse($order['created_at'])->utc(),
            updatedAt: CarbonImmutable::parse($order['updated_at'])->utc(),
        );
    }

    private function parseNullableDate(?string $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::parse($value)->utc();
    }
}
