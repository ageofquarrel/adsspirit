<?php

declare(strict_types=1);

namespace Tests\Unit\Requests\Marketplace;

use App\Http\Requests\Marketplace\StoreMarketplaceWebhookRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class StoreMarketplaceWebhookRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new StoreMarketplaceWebhookRequest)->rules();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function realWorldExamples(): array
    {
        return [
            'created' => ['order-created.json'],
            'updated' => ['order-updated.json'],
            'paid' => ['order-paid.json'],
            'shipped' => ['order-shipped.json'],
            'cancelled' => ['order-cancelled.json'],
        ];
    }

    #[DataProvider('realWorldExamples')]
    public function test_every_real_world_example_passes(string $file): void
    {
        $payload = json_decode(file_get_contents(base_path("task/examples/{$file}")), true);

        $validator = Validator::make($payload, $this->rules());

        $this->assertTrue($validator->passes(), $validator->errors()->toJson());
    }

    public function test_it_requires_id_type_created_at_and_data_order(): void
    {
        $payload = [];

        $validator = Validator::make($payload, $this->rules());

        $this->assertTrue($validator->fails());
        foreach (['id', 'type', 'created_at', 'data', 'data.order'] as $field) {
            $this->assertTrue($validator->errors()->has($field), "Expected a validation error on [{$field}].");
        }
    }

    public function test_it_fails_when_data_order_is_missing(): void
    {
        $payload = [
            'id' => 'evt_1',
            'type' => 'order.paid',
            'created_at' => now()->toIso8601String(),
            'data' => [],
        ];

        $validator = Validator::make($payload, $this->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('data.order', $validator->errors()->toArray());
    }

    public function test_it_rejects_an_unknown_order_status(): void
    {
        $payload = json_decode(file_get_contents(base_path('task/examples/order-paid.json')), true);
        $payload['data']['order']['status'] = 'refunded';

        $validator = Validator::make($payload, $this->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('data.order.status', $validator->errors()->toArray());
    }

    public function test_it_rejects_an_amount_without_two_decimal_places(): void
    {
        $payload = json_decode(file_get_contents(base_path('task/examples/order-paid.json')), true);
        $payload['data']['order']['total'] = '4909.5';

        $validator = Validator::make($payload, $this->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('data.order.total', $validator->errors()->toArray());
    }
}
