<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Регистрирует сервисы приложения.
     */
    public function register(): void {}

    /**
     * Запускает инициализацию сервисов приложения.
     */
    public function boot(): void {}
}
