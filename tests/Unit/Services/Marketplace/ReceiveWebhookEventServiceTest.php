<?php

namespace Tests\Unit\Services\Marketplace;

use App\DTO\Marketplace\ReceiveWebhookEventDTO;
use App\Services\Marketplace\ReceiveWebhookEventService;
use Tests\Doubles\Repositories\MarketplaceWebhookEventRepositoryMock;
use Tests\TestCase;

class ReceiveWebhookEventServiceTest extends TestCase
{
    public function test_it_stores_a_new_event_and_reports_it_as_not_duplicate(): void
    {
        // Arrange
        $repository = new MarketplaceWebhookEventRepositoryMock;
        $repository->existsByEventIdReturn = false;
        $service = new ReceiveWebhookEventService($repository);
        $data = new ReceiveWebhookEventDTO('evt_1', 'order.paid', ['id' => 'evt_1']);

        // Act
        $wasDuplicate = $service->handle($data);

        // Assert
        $this->assertFalse($wasDuplicate);
        $this->assertSame(1, $repository->createCallCount);
        $this->assertSame($data, $repository->lastCreatedWith);
    }

    public function test_it_skips_storing_when_the_event_already_exists(): void
    {
        // Arrange
        $repository = new MarketplaceWebhookEventRepositoryMock;
        $repository->existsByEventIdReturn = true;
        $service = new ReceiveWebhookEventService($repository);
        $data = new ReceiveWebhookEventDTO('evt_1', 'order.paid', ['id' => 'evt_1']);

        // Act
        $wasDuplicate = $service->handle($data);

        // Assert
        $this->assertTrue($wasDuplicate);
        $this->assertSame(0, $repository->createCallCount);
    }
}
