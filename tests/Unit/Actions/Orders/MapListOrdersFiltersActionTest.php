<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Orders;

use App\Actions\Orders\MapListOrdersFiltersAction;
use App\Enums\OrderStatus;
use Tests\TestCase;

final class MapListOrdersFiltersActionTest extends TestCase
{
    public function test_it_applies_the_documented_defaults_for_an_empty_payload(): void
    {
        $dto = (new MapListOrdersFiltersAction)->execute([]);

        $this->assertNull($dto->status);
        $this->assertNull($dto->email);
        $this->assertSame(1, $dto->page);
        $this->assertSame(20, $dto->perPage);
    }

    public function test_it_maps_the_given_filters(): void
    {
        $dto = (new MapListOrdersFiltersAction)->execute([
            'status' => 'paid',
            'email' => 'Anna@Example.com',
            'page' => '2',
            'per_page' => '50',
        ]);

        $this->assertSame(OrderStatus::Paid, $dto->status);
        $this->assertSame('Anna@Example.com', $dto->email);
        $this->assertSame(2, $dto->page);
        $this->assertSame(50, $dto->perPage);
    }
}
