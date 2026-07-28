<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capture_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('captured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('provider', 80)->default('manual_upload');
            $table->string('capture_type', 80);
            $table->string('device_name')->nullable();
            $table->string('device_serial')->nullable();
            $table->string('status', 40)->default('draft');
            $table->string('accuracy_class', 80)->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'status']);
            $table->index(['inspection_id', 'provider']);
            $table->index(['provider', 'capture_type']);
        });

        Schema::create('spatial_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('capture_session_id')->nullable()->constrained('capture_sessions')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('provider', 80)->default('manual_upload');
            $table->string('source_type', 80);
            $table->string('display_name')->nullable();
            $table->string('runtime_format', 40)->nullable();
            $table->string('original_format', 40)->nullable();
            $table->string('provider_model_id')->nullable();
            $table->string('external_url')->nullable();
            $table->string('file_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('status', 40)->default('active');
            $table->string('processing_status', 40)->default('ready');
            $table->boolean('is_primary')->default(false);
            $table->string('accuracy_class', 80)->nullable();
            $table->json('coordinate_transform')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'status']);
            $table->index(['inspection_id', 'status']);
            $table->index(['provider', 'source_type']);
            $table->index(['capture_session_id', 'processing_status']);
        });

        Schema::table('matterport_models', function (Blueprint $table) {
            $table->foreignId('spatial_model_id')
                ->nullable()
                ->after('inspection_id')
                ->constrained('spatial_models')
                ->nullOnDelete();
        });

        Schema::create('issue_markers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('spatial_model_id')->nullable()->constrained('spatial_models')->nullOnDelete();
            $table->foreignId('capture_session_id')->nullable()->constrained('capture_sessions')->nullOnDelete();
            $table->foreignId('phar_finding_id')->nullable()->constrained('phar_findings')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_provider', 80)->default('manual');
            $table->string('marker_type', 80)->default('issue');
            $table->string('title');
            $table->string('severity', 40)->default('medium');
            $table->string('status', 40)->default('open');
            $table->decimal('position_x', 12, 4)->nullable();
            $table->decimal('position_y', 12, 4)->nullable();
            $table->decimal('position_z', 12, 4)->nullable();
            $table->decimal('normal_x', 9, 6)->nullable();
            $table->decimal('normal_y', 9, 6)->nullable();
            $table->decimal('normal_z', 9, 6)->nullable();
            $table->string('room_name')->nullable();
            $table->string('surface_label')->nullable();
            $table->string('source_reference')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->json('attachments')->nullable();
            $table->json('metadata')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'status']);
            $table->index(['inspection_id', 'status']);
            $table->index(['spatial_model_id', 'status']);
            $table->index(['source_provider', 'marker_type']);
        });
    }

    public function down(): void
    {
        Schema::table('matterport_models', function (Blueprint $table) {
            $table->dropConstrainedForeignId('spatial_model_id');
        });

        Schema::dropIfExists('issue_markers');
        Schema::dropIfExists('spatial_models');
        Schema::dropIfExists('capture_sessions');
    }
};
