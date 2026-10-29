<?php

declare(strict_types=1);

namespace Tests\Unit\Requests\Orders;

use App\Http\Requests\Orders\ListOrdersRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class ListOrdersRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new ListOrdersRequest)->rules();
    }

    public function test_an_empty_payload_is_valid(): void
    {
        $validator = Validator::make([], $this->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_it_rejects_an_unknown_status(): void
    {
        $validator = Validator::make(['status' => 'refunded'], $this->rules());

        $this->assertTrue($validator->fails());
    }

    public function test_it_rejects_a_per_page_above_the_maximum(): void
    {
        $validator = Validator::make(['per_page' => 101], $this->rules());

        $this->assertTrue($validator->fails());
    }

    public function test_it_rejects_a_page_below_one(): void
    {
        $validator = Validator::make(['page' => 0], $this->rules());

        $this->assertTrue($validator->fails());
    }
}
