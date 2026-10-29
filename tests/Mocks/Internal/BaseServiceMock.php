<?php

declare(strict_types=1);

namespace Tests\Mocks\Internal;

use Mockery\MockInterface;

/**
 * Базовый класс для Mockery-моков, подменяющих зависимости приложения
 * в сервис-контейнере на время теста.
 */
abstract class BaseServiceMock
{
    /**
     * Регистрирует готовый мок в контейнере вместо реальной реализации.
     */
    protected static function mock(string $abstract, MockInterface $mock): void
    {
        app()->instance($abstract, $mock);
    }

    /**
     * Возвращает уже зарегистрированный в контейнере мок того же контракта,
     * чтобы один тест мог настроить ожидания на нескольких его методах.
     */
    protected static function existing(string $abstract): ?MockInterface
    {
        $instance = app()->bound($abstract) ? app($abstract) : null;

        return $instance instanceof MockInterface ? $instance : null;
    }
}
