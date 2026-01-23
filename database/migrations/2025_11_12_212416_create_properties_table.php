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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Property owner
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('set null');
            
            // Owner Details
            $table->string('owner_first_name');
            $table->string('owner_phone');
            $table->string('owner_email');
            
            // Property Administrator Details (optional)
            $table->string('admin_first_name')->nullable();
            $table->string('admin_last_name')->nullable();
            $table->string('admin_email')->nullable();
            $table->string('admin_phone')->nullable();
            
            // Property Details
            $table->string('property_name');
            $table->string('property_address');
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Canada');
            
            $table->enum('type', ['residential', 'commercial', 'mixed_use']);
            $table->string('property_subtype')->nullable(); // house, townhome, condo, duplex, multi-unit, office, retail, warehouse, etc.
            $table->integer('residential_units')->nullable(); // For residential/mixed-use: used for size factor calculation
            $table->decimal('mixed_use_commercial_weight', 5, 2)->nullable()->comment('Percentage (0-100) of commercial area for mixed-use properties');
            $table->integer('year_built')->nullable();
            $table->decimal('square_footage_interior', 10, 2)->default(0);
            $table->decimal('square_footage_green', 10, 2)->default(0);
            $table->decimal('square_footage_paved', 10, 2)->default(0);
            $table->decimal('square_footage_extra', 10, 2)->default(0);
            $table->decimal('total_square_footage', 10, 2)->default(0);
            
            $table->enum('occupied_by', ['owner', 'family', 'tenants', 'mixed'])->nullable();
            $table->boolean('has_pets')->default(false);
            $table->boolean('has_kids')->default(false);
            $table->enum('personality', ['calm', 'busy', 'luxury', 'high-use'])->nullable();
            
            // Problems & Sensitivities
            $table->text('known_problems')->nullable();
            $table->json('sensitivities')->nullable(); // allergies, water_damage, aging, eco_friendly, pet_safe, accessibility
            
            // Home Care Goals (from Step 3 of form)
            $table->json('care_goals')->nullable(); // comfort_beauty, protection_safety, exterior_grounds, convenience
            
            // Vision & Journey (from Step 1)
            $table->json('home_journey')->nullable(); // proactive_care, predictable_maintenance, etc.
            $table->json('home_feel')->nullable(); // safe_healthy, organized_peaceful, etc.
            
            // Planned Projects (from Step 5)
            $table->json('planned_projects')->nullable();
            $table->boolean('needs_design_support')->default(false);
            
            // Scheduling Preferences (from Step 6)
            $table->json('scheduling_style')->nullable(); // monthly, seasonal, on-demand, hybrid
            $table->json('preferred_days')->nullable();
            $table->json('preferred_times')->nullable();
            
            // Files
            $table->string('blueprint_file')->nullable();
            $table->json('property_photos')->nullable();
            
            $table->enum('status', ['pending_approval', 'approved', 'rejected'])->default('pending_approval');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Staff assignments
            $table->foreignId('project_manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('inspector_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('inspection_scheduled_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
