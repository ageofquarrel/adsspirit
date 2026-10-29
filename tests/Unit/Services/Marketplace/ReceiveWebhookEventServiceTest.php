<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Marketplace;

use App\DTO\Marketplace\MarketplaceWebhookPayloadDTO;
use App\DTO\Marketplace\ReceiveWebhookEventDTO;
use App\Models\MarketplaceWebhookEvent;
use App\Services\Marketplace\ReceiveWebhookEventService;
use Tests\Mocks\Internal\App\Repositories\Contracts\MarketplaceWebhookEventRepositoryMock;
use Tests\TestCase;

final class ReceiveWebhookEventServiceTest extends TestCase
{
    private function event(): ReceiveWebhookEventDTO
    {
        return new ReceiveWebhookEventDTO(
            'evt_1',
            'order.paid',
            new MarketplaceWebhookPayloadDTO('evt_1', 'order.paid', '2026-01-01T00:00:00+00:00', ['id' => 'ord_1']),
        );
    }

    public function test_it_stores_a_brand_new_event_and_reports_it_as_not_a_duplicate(): void
    {
        $data = $this->event();
        MarketplaceWebhookEventRepositoryMock::returnFindByEventId(null);
        MarketplaceWebhookEventRepositoryMock::returnCreate(new MarketplaceWebhookEvent(['event_id' => 'evt_1']), $data);

        $wasDuplicate = app(ReceiveWebhookEventService::class)->handle($data);

        $this->assertFalse($wasDuplicate);
    }

    public function test_it_skips_storing_an_event_that_was_already_fully_processed(): void
    {
        $data = $this->event();
        MarketplaceWebhookEventRepositoryMock::returnFindByEventId(new MarketplaceWebhookEvent(['processed_at' => now()]));

        $wasDuplicate = app(ReceiveWebhookEventService::class)->handle($data);

        $this->assertTrue($wasDuplicate);
    }

    public function test_it_does_not_skip_an_event_that_was_stored_but_never_finished_processing(): void
    {
        $data = $this->event();
        MarketplaceWebhookEventRepositoryMock::returnFindByEventId(new MarketplaceWebhookEvent(['processed_at' => null]));

        $wasDuplicate = app(ReceiveWebhookEventService::class)->handle($data);

        $this->assertFalse($wasDuplicate);
    }
}
