<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inspections')) {
            return;
        }

        $databaseName = DB::getDatabaseName();

        $indexExists = DB::table('information_schema.statistics')
            ->where('table_schema', $databaseName)
            ->where('table_name', 'inspections')
            ->where('index_name', 'inspections_service_package_id_index')
            ->exists();

        if ($indexExists) {
            Schema::table('inspections', function (Blueprint $table) {
                $table->dropIndex('inspections_service_package_id_index');
            });
        }

        $columnsToDrop = array_values(array_filter([
            'service_package_id',
            'service_package_name',
            'base_price_snapshot',
        ], fn (string $column): bool => Schema::hasColumn('inspections', $column)));

        if (empty($columnsToDrop)) {
            return;
        }

        Schema::table('inspections', function (Blueprint $table) use ($columnsToDrop) {
            $table->dropColumn($columnsToDrop);
        });
    }

    public function down(): void
    {
        // Intentionally left empty; this cleanup migration is not reversible.
    }
};
