<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Marketplace;

use App\Actions\Marketplace\MapReceiveWebhookEventAction;
use Tests\TestCase;

final class MapReceiveWebhookEventActionTest extends TestCase
{
    public function test_it_maps_the_raw_webhook_body_into_a_dto(): void
    {
        $payload = json_decode(file_get_contents(base_path('task/examples/order-paid.json')), true);

        $event = (new MapReceiveWebhookEventAction)->execute($payload);

        $this->assertSame($payload['id'], $event->eventId);
        $this->assertSame($payload['type'], $event->type);
        $this->assertSame($payload['id'], $event->payload->id);
        $this->assertSame($payload['type'], $event->payload->type);
        $this->assertSame($payload['created_at'], $event->payload->createdAt);
        $this->assertSame($payload['data']['order'], $event->payload->order);
    }
}
