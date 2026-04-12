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

        // If any legacy foreign keys still exist on these columns, drop them first.
        $legacyForeignKeyColumns = [
            'cpi_supply_material_id',
            'cpi_containment_category_id',
            'cpi_crawl_access_id',
            'cpi_roof_access_id',
            'cpi_equipment_requirement_id',
            'cpi_complexity_category_id',
        ];

        foreach ($legacyForeignKeyColumns as $column) {
            $constraint = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'inspections')
                ->where('COLUMN_NAME', $column)
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->value('CONSTRAINT_NAME');

            if ($constraint) {
                DB::statement("ALTER TABLE inspections DROP FOREIGN KEY `{$constraint}`");
            }
        }

        $columnsToDrop = [
            'cpi_unit_shutoffs',
            'cpi_shared_risers',
            'cpi_static_pressure',
            'cpi_isolation_zones',
            'domain_1_score',
            'domain_1_notes',
            'cpi_supply_material_id',
            'cpi_supply_material_name',
            'cpi_supply_material_score',
            'cpi_drain_material_unknown',
            'domain_2_score',
            'domain_2_notes',
            'building_age_calculated',
            'cpi_fixture_age',
            'cpi_systems_documented',
            'cpi_age_score_harmonised',
            'domain_3_score',
            'domain_3_notes',
            'cpi_containment_category_id',
            'cpi_containment_category_name',
            'cpi_containment_score',
            'domain_4_score',
            'domain_4_notes',
            'cpi_crawl_access_id',
            'cpi_crawl_access_name',
            'cpi_crawl_access_score',
            'cpi_roof_access_id',
            'cpi_roof_access_name',
            'cpi_roof_access_score',
            'cpi_equipment_requirement_id',
            'cpi_equipment_requirement_name',
            'cpi_equipment_requirement_score',
            'cpi_time_to_access',
            'cpi_accessibility_score_capped',
            'domain_5_score',
            'domain_5_notes',
            'cpi_complexity_category_id',
            'cpi_complexity_category_name',
            'cpi_complexity_score',
            'domain_6_score',
            'domain_6_notes',
            'cpi_band',
            'cpi_multiplier',
            'cpi_band_range_snapshot',
            'cpi_band_name_snapshot',
        ];

        $existingColumns = array_values(array_filter(
            $columnsToDrop,
            static fn (string $column): bool => Schema::hasColumn('inspections', $column)
        ));

        if ($existingColumns === []) {
            return;
        }

        Schema::table('inspections', function (Blueprint $table) use ($existingColumns) {
            $table->dropColumn($existingColumns);
        });
    }

    public function down(): void
    {
        // Intentionally left blank: legacy domain fields are deprecated and not restored on rollback.
    }
};
