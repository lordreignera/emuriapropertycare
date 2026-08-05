<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('twin_processing_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('capture_session_id')->nullable()->constrained('capture_sessions')->nullOnDelete();
            $table->foreignId('source_file_id')->nullable()->constrained('twin_source_files')->nullOnDelete();
            $table->foreignId('spatial_model_id')->nullable()->constrained('spatial_models')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('processor', 80);
            $table->string('job_type', 80);
            $table->string('queue_name', 80)->nullable();
            $table->string('status', 40)->default('queued');
            $table->string('input_storage_disk', 80)->nullable();
            $table->string('input_storage_path')->nullable();
            $table->string('output_storage_disk', 80)->nullable();
            $table->string('output_storage_path')->nullable();
            $table->unsignedInteger('timeout_seconds')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('processing_error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'status']);
            $table->index(['inspection_id', 'status']);
            $table->index(['source_file_id', 'status']);
            $table->index(['processor', 'job_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('twin_processing_jobs');
    }
};
