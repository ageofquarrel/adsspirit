<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Фильтр по email без учёта регистра в GET /api/orders использует
     * `LOWER(customer_email) = ?`, а это не может задействовать обычный
     * индекс на `customer_email` (MySQL вынужден сканировать все строки,
     * чтобы вычислить функцию). Функциональный индекс на выражении с
     * приведением к нижнему регистру позволяет использовать поиск по
     * индексу — MySQL 8.0.13+ поддерживает это напрямую. Не применяется
     * к sqlite-соединению в тестах (при таком объёме данных там это и
     * не нужно).
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE orders ADD INDEX orders_customer_email_lower_index ((LOWER(customer_email)))',
            );
        }
    }

    /**
     * Откатывает миграцию.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE orders DROP INDEX orders_customer_email_lower_index');
        }
    }
};
