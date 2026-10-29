<?php

namespace App\Http\Requests\Orders;

use App\DTO\Orders\ListOrdersFiltersDTO;
use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'email' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function toDto(): ListOrdersFiltersDTO
    {
        $status = $this->validated('status');

        return new ListOrdersFiltersDTO(
            status: $status !== null ? OrderStatus::from($status) : null,
            email: $this->validated('email'),
            page: (int) ($this->validated('page') ?? 1),
            perPage: (int) ($this->validated('per_page') ?? 20),
        );
    }
}
