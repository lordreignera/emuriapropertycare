<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_number')->unique();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Client
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('managed_by')->nullable()->constrained('users')->onDelete('set null'); // Project Manager
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', [
                'pending', 
                'inspection_scheduled', 
                'inspection_completed', 
                'scoping', 
                'quoted', 
                'client_review',
                'approved',
                'scheduled', 
                'in_progress', 
                'completed', 
                'cancelled'
            ])->default('pending');
            
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            
            $table->text('agreement_terms')->nullable();
            $table->timestamp('agreement_sent_at')->nullable();
            $table->timestamp('agreement_signed_at')->nullable();
            $table->string('agreement_file')->nullable();
            
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            
            $table->index(['property_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
