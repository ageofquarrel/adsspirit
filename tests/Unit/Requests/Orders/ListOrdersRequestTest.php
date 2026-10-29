<?php

namespace Tests\Unit\Requests\Orders;

use App\Enums\OrderStatus;
use App\Http\Requests\Orders\ListOrdersRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ListOrdersRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new ListOrdersRequest)->rules();
    }

    public function test_an_empty_payload_is_valid(): void
    {
        // Arrange & Act
        $validator = Validator::make([], $this->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_it_rejects_an_unknown_status(): void
    {
        // Arrange & Act
        $validator = Validator::make(['status' => 'refunded'], $this->rules());

        // Assert
        $this->assertTrue($validator->fails());
    }

    public function test_it_rejects_a_per_page_above_the_maximum(): void
    {
        // Arrange & Act
        $validator = Validator::make(['per_page' => 101], $this->rules());

        // Assert
        $this->assertTrue($validator->fails());
    }

    public function test_it_rejects_a_page_below_one(): void
    {
        // Arrange & Act
        $validator = Validator::make(['page' => 0], $this->rules());

        // Assert
        $this->assertTrue($validator->fails());
    }

    public function test_to_dto_applies_the_documented_defaults(): void
    {
        // Arrange
        $request = ListOrdersRequest::create('/api/orders', 'GET');
        $request->setContainer(app());
        $request->validateResolved();

        // Act
        $dto = $request->toDto();

        // Assert
        $this->assertNull($dto->status);
        $this->assertNull($dto->email);
        $this->assertSame(1, $dto->page);
        $this->assertSame(20, $dto->perPage);
    }

    public function test_to_dto_maps_the_given_filters(): void
    {
        // Arrange
        $request = ListOrdersRequest::create('/api/orders', 'GET', [
            'status' => 'paid',
            'email' => 'Anna@Example.com',
            'page' => '2',
            'per_page' => '50',
        ]);
        $request->setContainer(app());
        $request->validateResolved();

        // Act
        $dto = $request->toDto();

        // Assert
        $this->assertSame(OrderStatus::Paid, $dto->status);
        $this->assertSame('Anna@Example.com', $dto->email);
        $this->assertSame(2, $dto->page);
        $this->assertSame(50, $dto->perPage);
    }
}
