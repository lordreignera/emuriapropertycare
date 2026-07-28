<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matterport_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('model_sid', 80);
            $table->string('model_name')->nullable();
            $table->string('model_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->enum('status', ['draft', 'active', 'archived'])->default('active');
            $table->date('scanned_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('model_sid');
            $table->index(['property_id', 'status']);
            $table->index(['inspection_id', 'status']);
            $table->unique('inspection_id', 'matterport_models_inspection_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matterport_models');
    }
};
