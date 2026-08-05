<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issue_markers', function (Blueprint $table) {
            if (!Schema::hasColumn('issue_markers', 'camera_position')) {
                $table->json('camera_position')->nullable()->after('normal_z');
            }

            if (!Schema::hasColumn('issue_markers', 'camera_target')) {
                $table->json('camera_target')->nullable()->after('camera_position');
            }

            if (!Schema::hasColumn('issue_markers', 'object_uuid')) {
                $table->string('object_uuid')->nullable()->after('camera_target');
            }

            if (!Schema::hasColumn('issue_markers', 'provenance')) {
                $table->json('provenance')->nullable()->after('metadata');
            }
        });
    }

    public function down(): void
    {
        Schema::table('issue_markers', function (Blueprint $table) {
            if (Schema::hasColumn('issue_markers', 'provenance')) {
                $table->dropColumn('provenance');
            }

            if (Schema::hasColumn('issue_markers', 'object_uuid')) {
                $table->dropColumn('object_uuid');
            }

            if (Schema::hasColumn('issue_markers', 'camera_target')) {
                $table->dropColumn('camera_target');
            }

            if (Schema::hasColumn('issue_markers', 'camera_position')) {
                $table->dropColumn('camera_position');
            }
        });
    }
};
