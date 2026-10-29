<?php

namespace Tests\Unit\Repositories;

use App\DTO\Marketplace\ReceiveWebhookEventDTO;
use App\Models\MarketplaceWebhookEvent;
use App\Repositories\MarketplaceWebhookEventRepository;
use Tests\TestCase;

class MarketplaceWebhookEventRepositoryTest extends TestCase
{
    public function test_find_by_event_id_returns_the_stored_event(): void
    {
        // Arrange
        MarketplaceWebhookEvent::query()->create([
            'event_id' => 'evt_1',
            'type' => 'order.paid',
            'payload' => ['id' => 'evt_1'],
        ]);
        $repository = new MarketplaceWebhookEventRepository;

        // Act & Assert
        $this->assertSame('order.paid', $repository->findByEventId('evt_1')->type);
        $this->assertNull($repository->findByEventId('evt_2'));
    }

    public function test_mark_processed_sets_the_timestamp(): void
    {
        // Arrange
        MarketplaceWebhookEvent::query()->create([
            'event_id' => 'evt_1',
            'type' => 'order.paid',
            'payload' => ['id' => 'evt_1'],
        ]);
        $repository = new MarketplaceWebhookEventRepository;

        // Act
        $repository->markProcessed('evt_1');

        // Assert
        $this->assertNotNull($repository->findByEventId('evt_1')->processed_at);
    }

    public function test_create_persists_the_event_with_its_payload(): void
    {
        // Arrange
        $repository = new MarketplaceWebhookEventRepository;
        $data = new ReceiveWebhookEventDTO('evt_1', 'order.paid', ['id' => 'evt_1', 'type' => 'order.paid']);

        // Act
        $event = $repository->create($data);

        // Assert
        $this->assertDatabaseHas('marketplace_webhook_events', [
            'event_id' => 'evt_1',
            'type' => 'order.paid',
        ]);
        $this->assertSame(['id' => 'evt_1', 'type' => 'order.paid'], $event->payload);
    }
}
