<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Client
            $table->integer('year');
            
            $table->decimal('total_subscription_cost', 10, 2)->default(0);
            $table->decimal('total_services_value', 10, 2)->default(0);
            $table->decimal('savings_amount', 10, 2)->default(0);
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->unique(['user_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings');
    }
};
