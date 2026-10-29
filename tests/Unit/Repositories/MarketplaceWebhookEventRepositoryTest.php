<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\DTO\Marketplace\MarketplaceWebhookPayloadDTO;
use App\DTO\Marketplace\ReceiveWebhookEventDTO;
use App\Models\MarketplaceWebhookEvent;
use App\Repositories\MarketplaceWebhookEventRepository;
use Tests\TestCase;

final class MarketplaceWebhookEventRepositoryTest extends TestCase
{
    public function test_find_by_event_id_returns_the_stored_event(): void
    {
        MarketplaceWebhookEvent::query()->create([
            'event_id' => 'evt_1',
            'type' => 'order.paid',
            'payload' => ['id' => 'evt_1'],
        ]);
        $repository = new MarketplaceWebhookEventRepository;

        $this->assertSame('order.paid', $repository->findByEventId('evt_1')->type);
        $this->assertNull($repository->findByEventId('evt_2'));
    }

    public function test_mark_processed_sets_the_timestamp(): void
    {
        MarketplaceWebhookEvent::query()->create([
            'event_id' => 'evt_1',
            'type' => 'order.paid',
            'payload' => ['id' => 'evt_1'],
        ]);
        $repository = new MarketplaceWebhookEventRepository;

        $repository->markProcessed('evt_1');

        $this->assertNotNull($repository->findByEventId('evt_1')->processed_at);
    }

    public function test_create_persists_the_event_with_its_payload(): void
    {
        $repository = new MarketplaceWebhookEventRepository;
        $data = new ReceiveWebhookEventDTO(
            'evt_1',
            'order.paid',
            new MarketplaceWebhookPayloadDTO('evt_1', 'order.paid', '2026-01-01T00:00:00+00:00', ['id' => 'ord_1']),
        );

        $event = $repository->create($data);

        $this->assertDatabaseHas('marketplace_webhook_events', [
            'event_id' => 'evt_1',
            'type' => 'order.paid',
        ]);
        $this->assertSame([
            'id' => 'evt_1',
            'type' => 'order.paid',
            'created_at' => '2026-01-01T00:00:00+00:00',
            'data' => ['order' => ['id' => 'ord_1']],
        ], $event->payload);
    }
}
