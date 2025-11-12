<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress_trackers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('updated_by')->constrained('users')->onDelete('restrict');
            
            // Section A - Scope of Work Progress (stored as JSON for flexibility)
            $table->json('scope_progress')->nullable(); // Array of {system, issue, location, status, start_date, finish_date, notes}
            
            // Overall Progress
            $table->integer('percent_complete')->default(0); // 0-100
            $table->string('phase')->nullable(); // Current phase name
            $table->text('remarks')->nullable();
            
            // Section D - Client Acceptance
            $table->boolean('client_accepted')->default(false);
            $table->timestamp('client_accepted_at')->nullable();
            $table->string('client_signature')->nullable(); // Path to signature image
            $table->json('client_review')->nullable(); // {professionalism, integrity, timeliness, quality, cleanliness, communication}
            $table->boolean('client_recommends')->nullable();
            $table->text('client_review_text')->nullable();
            
            $table->timestamps();
            
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_trackers');
    }
};
