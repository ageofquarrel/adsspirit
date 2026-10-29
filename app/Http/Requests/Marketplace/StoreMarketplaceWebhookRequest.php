<?php

namespace App\Http\Requests\Marketplace;

use App\Enums\OrderStatus;
use App\Http\Middleware\VerifyMarketplaceWebhookSignature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreMarketplaceWebhookRequest extends FormRequest
{
    /**
     * The webhook signature is already verified by
     * {@see VerifyMarketplaceWebhookSignature}.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $amount = ['required', 'string', 'regex:/^-?\d+\.\d{2}$/'];

        return [
            'id' => ['required', 'string'],
            'type' => ['required', 'string'],
            'created_at' => ['required', 'date'],

            'data' => ['required', 'array'],
            'data.order' => ['required', 'array'],
            'data.order.id' => ['required', 'string'],
            'data.order.number' => ['required', 'string'],
            'data.order.status' => ['required', Rule::enum(OrderStatus::class)],
            'data.order.currency' => ['required', 'string', 'size:3'],

            'data.order.customer' => ['required', 'array'],
            'data.order.customer.email' => ['required', 'string'],
            'data.order.customer.name' => ['required', 'string'],
            'data.order.customer.phone' => ['nullable', 'string'],

            'data.order.items' => ['required', 'array'],
            'data.order.items.*.sku' => ['required', 'string'],
            'data.order.items.*.name' => ['required', 'string'],
            'data.order.items.*.quantity' => ['required', 'integer', 'min:1'],
            'data.order.items.*.price' => $amount,

            'data.order.delivery_price' => $amount,
            'data.order.discount' => $amount,
            'data.order.total' => $amount,

            'data.order.shipment' => ['nullable', 'array'],
            'data.order.shipment.carrier' => ['required_with:data.order.shipment', 'string'],
            'data.order.shipment.tracking_number' => ['required_with:data.order.shipment', 'string'],
            'data.order.shipment.tracking_url' => ['required_with:data.order.shipment', 'string'],

            'data.order.paid_at' => ['nullable', 'date'],
            'data.order.cancelled_at' => ['nullable', 'date'],
            'data.order.cancel_reason' => ['nullable', 'string'],

            'data.order.created_at' => ['required', 'date'],
            'data.order.updated_at' => ['required', 'date'],
        ];
    }
}
