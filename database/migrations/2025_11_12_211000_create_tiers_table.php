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
        Schema::create('tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tier 1, Tier 2, etc.
            $table->string('slug')->unique(); // basic-care, essential, enhanced, premium, elite
            $table->string('icon')->nullable(); // 🌿, 🧰, ⚙️, 🏛, 👑
            $table->string('experience'); // "Preventive Essentials", etc.
            $table->text('description');
            $table->json('features'); // Array of included services
            $table->decimal('monthly_price', 10, 2);
            $table->decimal('annual_price', 10, 2);
            $table->decimal('coverage_limit', 10, 2)->nullable(); // Max covered amount per project
            $table->text('designed_for')->nullable(); // Target audience description
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiers');
    }
};
