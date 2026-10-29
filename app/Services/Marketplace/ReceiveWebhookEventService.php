<?php

declare(strict_types=1);

namespace App\Services\Marketplace;

use App\DTO\Marketplace\ReceiveWebhookEventDTO;
use App\Repositories\Contracts\MarketplaceWebhookEventRepositoryInterface;

final readonly class ReceiveWebhookEventService
{
    public function __construct(
        private MarketplaceWebhookEventRepositoryInterface $repository,
    ) {}

    /**
     * Сохраняет событие при первом появлении. Если оно уже было сохранено
     * предыдущей попыткой доставки, но обработка так и не завершилась
     * (например, воркер упал между сохранением и применением к заказу),
     * метод *не* пропускает его — дублем без действия считается только
     * полностью обработанное событие, поэтому повтор от MarketHub всё ещё
     * может довести работу до конца.
     *
     * @return bool `true`, если событие уже было полностью обработано ранее.
     */
    public function handle(ReceiveWebhookEventDTO $data): bool
    {
        $existing = $this->repository->findByEventId($data->eventId);

        if ($existing === null) {
            $this->repository->create($data);

            return false;
        }

        return $existing->processed_at !== null;
    }

    public function markProcessed(string $eventId): void
    {
        $this->repository->markProcessed($eventId);
    }
}
