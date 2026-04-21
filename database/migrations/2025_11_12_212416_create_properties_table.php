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
            $table->string('property_code', 20)->unique();
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
            $table->string('property_brand', 100)->nullable();
            $table->string('property_address');
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Canada');

            $table->enum('type', ['residential', 'commercial', 'mixed_use']);
            $table->string('property_subtype')->nullable();
            $table->integer('residential_units')->nullable();
            $table->decimal('mixed_use_commercial_weight', 5, 2)->nullable()->comment('Percentage (0-100) of commercial area for mixed-use properties');
            $table->integer('year_built')->nullable();
            $table->decimal('square_footage_interior', 10, 2)->default(0);
            $table->decimal('square_footage_green', 10, 2)->default(0);
            $table->decimal('square_footage_paved', 10, 2)->default(0);
            $table->decimal('square_footage_extra', 10, 2)->default(0);
            $table->decimal('total_square_footage', 10, 2)->default(0);

            // Tenant support
            $table->boolean('has_tenants')->default(false);
            $table->integer('number_of_units')->default(1);
            $table->string('tenant_common_password')->nullable();

            $table->enum('occupied_by', ['owner', 'family', 'tenants', 'mixed'])->nullable();
            $table->boolean('has_pets')->default(false);
            $table->boolean('has_kids')->default(false);
            $table->enum('personality', ['calm', 'busy', 'luxury', 'high-use'])->nullable();
            $table->text('personality_notes')->nullable();

            // Problems & Sensitivities
            $table->text('known_problems')->nullable();
            $table->json('sensitivities')->nullable();

            // Home Care Goals (from Step 3 of form)
            $table->json('care_goals')->nullable();

            // Vision & Journey (from Step 1)
            $table->json('home_journey')->nullable();
            $table->json('home_feel')->nullable();

            // Planned Projects (from Step 5)
            $table->json('planned_projects')->nullable();
            $table->boolean('needs_design_support')->default(false);

            // Scheduling Preferences (from Step 6)
            $table->json('scheduling_style')->nullable();
            $table->json('preferred_days')->nullable();
            $table->json('preferred_times')->nullable();

            // Files
            $table->string('blueprint_file')->nullable();
            $table->json('property_photos')->nullable();

            $table->enum('status', ['pending_approval', 'approved', 'rejected', 'awaiting_inspection'])->default('pending_approval');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');

            // Complexity scoring
            $table->integer('current_complexity_score')->default(0);
            $table->string('recommended_tier')->nullable();

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
