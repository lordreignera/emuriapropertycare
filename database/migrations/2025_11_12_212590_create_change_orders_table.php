<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_orders', function (Blueprint $table) {
            $table->id();
            $table->string('change_order_number')->unique();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('restrict');
            
            $table->string('title');
            $table->text('description');
            $table->text('reason')->nullable();
            
            $table->decimal('added_cost', 10, 2)->default(0);
            $table->integer('schedule_impact_days')->default(0);
            
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            $table->timestamps();
            
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_orders');
    }
};
