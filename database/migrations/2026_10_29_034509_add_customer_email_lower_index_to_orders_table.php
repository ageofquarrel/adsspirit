<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The case-insensitive email filter in GET /api/orders does
     * `LOWER(customer_email) = ?`, which can't use a plain index on
     * `customer_email` (MySQL has to scan every row to evaluate the
     * function). A functional index on the lowercased expression lets it
     * use an index seek instead — MySQL 8.0.13+ supports this directly.
     * Not applicable to the sqlite connection used in tests (not needed
     * at that scale anyway).
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
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE orders DROP INDEX orders_customer_email_lower_index');
        }
    }
};
