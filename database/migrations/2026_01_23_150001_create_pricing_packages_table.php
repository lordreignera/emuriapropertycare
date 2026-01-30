<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_packages', function (Blueprint $table) {
            $table->id();
            $table->string('package_name', 50)->unique(); // Essentials, Premium, White-Glove
            $table->text('description')->nullable();
            $table->json('features')->nullable(); // Array of features
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
        });

        // Pivot table: Package × Property Type = Price
        Schema::create('package_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_package_id')->constrained('pricing_packages')->onDelete('cascade');
            $table->unsignedBigInteger('property_type_id');
            $table->decimal('base_monthly_price', 10, 2); // Base price per month
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['pricing_package_id', 'property_type_id']);
            $table->index(['pricing_package_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_pricing');
        Schema::dropIfExists('pricing_packages');
    }
};
