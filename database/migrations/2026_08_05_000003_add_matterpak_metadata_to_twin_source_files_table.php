<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('twin_source_files', function (Blueprint $table) {
            if (!Schema::hasColumn('twin_source_files', 'parent_source_file_id')) {
                $table->foreignId('parent_source_file_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('twin_source_files')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('twin_source_files', 'relative_path')) {
                $table->string('relative_path')->nullable()->after('stored_filename');
            }

            if (!Schema::hasColumn('twin_source_files', 'file_role')) {
                $table->string('file_role', 60)->nullable()->after('source_type');
            }
        });

        Schema::table('twin_source_files', function (Blueprint $table) {
            $table->index(['parent_source_file_id', 'file_role'], 'twin_source_parent_role_index');
            $table->index(['inspection_id', 'file_role'], 'twin_source_inspection_role_index');
        });
    }

    public function down(): void
    {
        Schema::table('twin_source_files', function (Blueprint $table) {
            $table->dropIndex('twin_source_parent_role_index');
            $table->dropIndex('twin_source_inspection_role_index');
        });

        Schema::table('twin_source_files', function (Blueprint $table) {
            if (Schema::hasColumn('twin_source_files', 'parent_source_file_id')) {
                $table->dropConstrainedForeignId('parent_source_file_id');
            }

            if (Schema::hasColumn('twin_source_files', 'file_role')) {
                $table->dropColumn('file_role');
            }

            if (Schema::hasColumn('twin_source_files', 'relative_path')) {
                $table->dropColumn('relative_path');
            }
        });
    }
};
