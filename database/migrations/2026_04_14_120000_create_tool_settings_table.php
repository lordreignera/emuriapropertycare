<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tool_settings', function (Blueprint $table) {
            $table->id();
            $table->string('tool_name', 150);
            $table->foreignId('system_id')->nullable()->constrained('systems')->nullOnDelete();
            $table->foreignId('subsystem_id')->nullable()->constrained('subsystems')->nullOnDelete();
            $table->foreignId('finding_template_setting_id')->nullable()->constrained('finding_template_settings')->nullOnDelete();
            $table->enum('ownership_status', ['owned', 'hired'])->default('owned');
            $table->enum('availability_status', ['available', 'non_available'])->default('available');
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['system_id', 'subsystem_id']);
            $table->index(['ownership_status', 'availability_status']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_settings');
    }
};
