<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('restrict'); // Technician/Crew member
            
            $table->date('log_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('hours_worked', 5, 2)->default(0);
            
            $table->string('crew_lead')->nullable();
            $table->json('crew_members')->nullable(); // Array of crew member names/IDs
            
            $table->text('work_completed');
            $table->text('activity')->nullable();
            
            // Section B - Media & QC
            $table->json('before_photos')->nullable();
            $table->string('before_video')->nullable();
            $table->json('during_photos')->nullable();
            $table->json('after_photos')->nullable();
            $table->text('crew_notes')->nullable();
            $table->text('qc_notes')->nullable();
            
            // Section C - Daily Log
            $table->boolean('site_cleaned')->default(false);
            $table->boolean('tools_returned')->default(false);
            $table->boolean('supervisor_checked')->default(false);
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('supervisor_checked_at')->nullable();
            
            $table->json('materials_used')->nullable();
            $table->text('remarks')->nullable();
            
            $table->timestamps();
            
            $table->index(['project_id', 'log_date']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_logs');
    }
};
