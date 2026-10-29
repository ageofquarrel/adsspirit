# MarketHub Orders Service

Laravel-сервис для приёма вебхуков маркетплейса, хранения заказов и отдачи их через API.
Задание — в [`task/README.md`](task/README.md).

## Стек

- PHP 8.4 (образ `php:8.4-fpm-alpine`), Laravel 13
- Nginx как веб-сервер
- MySQL 8.4
- Mailpit — перехват писем
- Отдельный контейнер с воркером очереди (`php artisan queue:work`)

Всё поднимается через `docker-compose.yml` и `Dockerfile` в корне репозитория.

## Запуск с нуля

1. Скопировать файл окружения:

   ```bash
   cp .env.example .env
   ```

   При необходимости поменяйте `MARKETPLACE_WEBHOOK_SECRET` — секрет для проверки подписи
   вебхуков маркетплейса (по умолчанию `change-me`).

2. Собрать образы и поднять контейнеры:

   ```bash
   docker compose up -d --build
   ```

3. Сгенерировать ключ приложения и накатить миграции:

   ```bash
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate --force
   ```

Готово. Адреса:

- **Приложение / API:** http://localhost:8090
- **Админка:** http://localhost:8090/admin/orders
- **Mailpit (веб-интерфейс писем):** http://localhost:8025

Порт приложения, порт MySQL и порты Mailpit можно переопределить переменными окружения
`APP_PORT`, `FORWARD_DB_PORT`, `FORWARD_MAILPIT_PORT`, `FORWARD_MAILPIT_SMTP_PORT`
(см. `docker-compose.yml`).

## Тестовые данные

Чтобы руками посмотреть админку/API не отправляя вебхуки, есть сидер — генерирует фейковыми
данными (`Faker`) 40 заказов (по 8 на каждый статус: `created`, `paid`, `shipped`, `delivered`,
`cancelled`) с позициями и реалистичной историей статусов:

```bash
docker compose exec app php artisan migrate:fresh --seed --force
```

(`migrate:fresh` полностью пересоздаёт таблицы — если нужно просто досеять данные без сброса,
используйте `php artisan db:seed`). Сидер — `database/seeders/OrderSeeder.php`.

## API

- `POST /api/webhooks/marketplace` — приём вебхуков MarketHub (подпись проверяется
  `MARKETPLACE_WEBHOOK_SECRET`; пример отправки — `task/examples/send-event.php`).
- `GET /api/orders` — список заказов, фильтры `status`/`email`, пагинация `page`/`per_page`.
  Работает на тех же данных, что и админка (сидер ниже или реально принятые вебхуки) —
  отдельно заполнять API нечем, это одни и те же таблицы.
- `GET /api/orders/{id}` — карточка заказа с позициями и историей статусов.

Контракт — в [`task/openapi.yaml`](task/openapi.yaml). Принятые решения и допущения — в
[`DECISIONS.md`](DECISIONS.md).

### Ручная проверка вебхука и писем

Приём вебхука и отправку писем нельзя «засеять» в БД напрямую — это сквозной сценарий
(подпись → идемпотентность → переход статуса → письмо в очереди), который нужно реально
прогнать через HTTP. Готовый подписывающий скрипт уже есть в задании:

```bash
docker compose exec -e MARKETPLACE_WEBHOOK_SECRET=change-me app \
  php task/examples/send-event.php http://nginx/api/webhooks/marketplace task/examples/order-paid.json
```

(вместо `order-paid.json` — любой файл из `task/examples/`). Письмо появится в Mailpit
(http://localhost:8025) через пару секунд — воркер очереди опрашивает БД раз в 3 секунды
(`--sleep=3` в `docker-compose.yml`), это не баг.

## Полезные команды

```bash
# Запустить тесты
docker compose exec app php artisan test

# Зайти в контейнер приложения
docker compose exec app sh

# Посмотреть логи воркера очереди
docker compose logs -f queue

# Остановить и удалить контейнеры
docker compose down

# Остановить и удалить контейнеры вместе с данными (MySQL, vendor)
docker compose down -v
```

## Структура docker-окружения

- `Dockerfile` — образ PHP-FPM 8.4 с нужными расширениями (`pdo_mysql`, `mbstring`, `bcmath`,
  `gd`, `zip`, `intl`, `opcache` и т. д.) и установленными зависимостями Composer.
- `docker-compose.yml` — сервисы `app` (PHP-FPM), `nginx`, `mysql`, `mailpit`, `queue`
  (воркер очереди на том же образе, что и `app`).
- `docker/nginx/default.conf` — конфигурация Nginx, проксирующая `*.php` в `app:9000`.

Код приложения примонтирован в контейнеры `app`/`queue`/`nginx` как volume, поэтому изменения
на хосте сразу видны внутри контейнеров без пересборки образа (кроме изменений `composer.json` —
после них нужно пересобрать: `docker compose up -d --build`).
