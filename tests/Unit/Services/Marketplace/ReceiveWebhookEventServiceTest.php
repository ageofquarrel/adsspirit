<?php

namespace Tests\Unit\Services\Marketplace;

use App\DTO\Marketplace\ReceiveWebhookEventDTO;
use App\Models\MarketplaceWebhookEvent;
use App\Services\Marketplace\ReceiveWebhookEventService;
use Tests\Doubles\Repositories\MarketplaceWebhookEventRepositoryMock;
use Tests\TestCase;

class ReceiveWebhookEventServiceTest extends TestCase
{
    public function test_it_stores_a_brand_new_event_and_reports_it_as_not_a_duplicate(): void
    {
        // Arrange
        $repository = new MarketplaceWebhookEventRepositoryMock;
        $repository->findByEventIdReturn = null;
        $service = new ReceiveWebhookEventService($repository);
        $data = new ReceiveWebhookEventDTO('evt_1', 'order.paid', ['id' => 'evt_1']);

        // Act
        $wasDuplicate = $service->handle($data);

        // Assert
        $this->assertFalse($wasDuplicate);
        $this->assertSame(1, $repository->createCallCount);
        $this->assertSame($data, $repository->lastCreatedWith);
    }

    public function test_it_skips_storing_an_event_that_was_already_fully_processed(): void
    {
        // Arrange
        $repository = new MarketplaceWebhookEventRepositoryMock;
        $repository->findByEventIdReturn = new MarketplaceWebhookEvent(['processed_at' => now()]);
        $service = new ReceiveWebhookEventService($repository);
        $data = new ReceiveWebhookEventDTO('evt_1', 'order.paid', ['id' => 'evt_1']);

        // Act
        $wasDuplicate = $service->handle($data);

        // Assert
        $this->assertTrue($wasDuplicate);
        $this->assertSame(0, $repository->createCallCount);
    }

    public function test_it_does_not_skip_an_event_that_was_stored_but_never_finished_processing(): void
    {
        // Arrange — e.g. the worker crashed between storing the event and
        // applying it to the order; a retry must still be allowed to finish.
        $repository = new MarketplaceWebhookEventRepositoryMock;
        $repository->findByEventIdReturn = new MarketplaceWebhookEvent(['processed_at' => null]);
        $service = new ReceiveWebhookEventService($repository);
        $data = new ReceiveWebhookEventDTO('evt_1', 'order.paid', ['id' => 'evt_1']);

        // Act
        $wasDuplicate = $service->handle($data);

        // Assert
        $this->assertFalse($wasDuplicate);
        $this->assertSame(0, $repository->createCallCount);
    }
}
