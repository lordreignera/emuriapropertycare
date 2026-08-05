<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('twin_source_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_source_file_id')->nullable()->constrained('twin_source_files')->nullOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('capture_session_id')->nullable()->constrained('capture_sessions')->nullOnDelete();
            $table->foreignId('spatial_model_id')->nullable()->constrained('spatial_models')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('storage_disk', 80)->nullable();
            $table->string('storage_path')->nullable();
            $table->string('original_filename');
            $table->string('stored_filename')->nullable();
            $table->string('relative_path')->nullable();
            $table->string('extension', 20);
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('checksum_sha256', 64)->nullable();
            $table->string('source_type', 40);
            $table->string('file_role', 60)->nullable();
            $table->string('processing_status', 40)->default('uploaded');
            $table->text('processing_error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'source_type']);
            $table->index(['inspection_id', 'processing_status']);
            $table->index(['capture_session_id', 'source_type']);
            $table->index(['spatial_model_id', 'processing_status']);
            $table->index('checksum_sha256');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('twin_source_files');
    }
};
