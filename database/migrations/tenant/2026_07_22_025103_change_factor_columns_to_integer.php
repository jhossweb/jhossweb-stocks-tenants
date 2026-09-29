<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE movements_purchases ALTER COLUMN mov_factor TYPE integer USING mov_factor::integer');
        DB::statement('ALTER TABLE movements_sales ALTER COLUMN mvs_factor TYPE integer USING mvs_factor::integer');
        DB::statement('ALTER TABLE movements_inventories ALTER COLUMN min_factor TYPE integer USING min_factor::integer');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE movements_purchases ALTER COLUMN mov_factor TYPE varchar(255) USING mov_factor::varchar(255)');
        DB::statement('ALTER TABLE movements_sales ALTER COLUMN mvs_factor TYPE varchar(255) USING mov_factor::varchar(255)');
        DB::statement('ALTER TABLE movements_inventories ALTER COLUMN min_factor TYPE varchar(255) USING mov_factor::varchar(255)');
    }
};
