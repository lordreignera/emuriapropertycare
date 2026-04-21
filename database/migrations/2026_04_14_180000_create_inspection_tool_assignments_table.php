<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_tool_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tool_setting_id')->nullable()->constrained('tool_settings')->nullOnDelete();
            $table->foreignId('system_id')->nullable()->constrained('systems')->nullOnDelete();
            $table->foreignId('subsystem_id')->nullable()->constrained('subsystems')->nullOnDelete();
            $table->string('tool_name');
            $table->unsignedSmallInteger('quantity')->default(0);
            $table->string('ownership_status', 30)->nullable();
            $table->string('availability_status', 30)->nullable();
            $table->unsignedInteger('finding_count')->default(0);
            $table->text('assign_notes')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('return_notes')->nullable();
            $table->timestamps();

            $table->unique(['inspection_id', 'tool_name'], 'inspection_tool_assignments_unique_tool');
            $table->index(['inspection_id', 'availability_status'], 'ita_insp_avail_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_tool_assignments');
    }
};
