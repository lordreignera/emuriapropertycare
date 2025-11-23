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
        // 1. Add property_code and tenant support to properties table
        Schema::table('properties', function (Blueprint $table) {
            $table->string('property_code', 20)->unique()->after('id');
            $table->string('property_brand', 100)->nullable()->after('property_name');
            $table->boolean('has_tenants')->default(false)->after('property_brand');
            $table->integer('number_of_units')->default(1)->after('has_tenants');
            $table->string('tenant_common_password')->nullable()->after('number_of_units');
        });

        // 2. Create tenants table (simplified - login via property code + tenant number)
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->integer('tenant_number'); // 1, 2, 3, 4...
            $table->string('tenant_login')->unique(); // APP12-1, APP12-2, etc.
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('unit_number', 50)->nullable();
            $table->boolean('can_report_emergency')->default(true);
            $table->enum('status', ['active', 'inactive', 'moved_out'])->default('active');
            $table->date('move_in_date')->nullable();
            $table->date('move_out_date')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->unique(['property_id', 'tenant_number']);
            $table->index('tenant_login');
        });

        // 3. Create products table (replaces tiers - admin managed)
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code', 50)->unique();
            $table->string('product_name');
            $table->text('description')->nullable();
            $table->enum('category', [
                'maintenance',
                'inspection',
                'repair',
                'emergency',
                'preventive',
                'subscription_package',
                'custom'
            ])->default('custom');
            $table->enum('pricing_type', [
                'fixed',           // One-time fixed price
                'component_based', // Calculate from components
                'subscription',    // Recurring subscription
                'pay_per_use'      // Usage-based
            ])->default('component_based');
            $table->decimal('base_price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_customizable')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->json('metadata')->nullable(); // Additional product info
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. Create product_components table
        Schema::create('product_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('component_name');
            $table->text('description')->nullable();
            $table->enum('calculation_type', [
                'fixed',        // Fixed cost (e.g., $150)
                'multiply',     // Quantity × Unit Cost
                'add',          // Sum of sub-items
                'percentage',   // % of another component
                'hourly'        // Hours × Hourly Rate
            ])->default('fixed');
            $table->string('parameter_name')->nullable(); // e.g., "hours", "quantity", "units"
            $table->decimal('parameter_value', 10, 2)->default(1);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->decimal('calculated_cost', 10, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_customizable')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        // 5. Create client_custom_products table (products customized per client)
        Schema::create('client_custom_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('property_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('base_product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('inspection_id')->nullable()->constrained()->onDelete('set null');
            $table->string('custom_product_name');
            $table->text('custom_description')->nullable();
            $table->json('customized_components'); // Modified component values
            $table->decimal('total_price', 10, 2)->default(0);
            $table->enum('pricing_model', [
                'one_time',
                'pay_as_you_go',
                'monthly_subscription',
                'annual_subscription',
                'project_based'
            ])->default('one_time');
            $table->decimal('monthly_price', 10, 2)->nullable();
            $table->decimal('annual_price', 10, 2)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->enum('status', [
                'draft',
                'offered',
                'accepted',
                'declined',
                'active',
                'expired',
                'cancelled'
            ])->default('draft');
            $table->timestamp('offered_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'status']);
            $table->index(['property_id', 'status']);
        });

        // 6. Create tenant_emergency_reports table
        Schema::create('tenant_emergency_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->string('report_number')->unique();
            $table->enum('emergency_type', [
                'plumbing',
                'electrical',
                'heating',
                'cooling',
                'security',
                'structural',
                'fire',
                'water_damage',
                'gas_leak',
                'other'
            ]);
            $table->enum('urgency', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->text('description');
            $table->string('location_in_property')->nullable();
            $table->json('photos')->nullable();
            $table->json('floor_plan_pin')->nullable(); // {x: 100, y: 200, floor: 1}
            $table->timestamp('reported_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->enum('status', [
                'reported',
                'acknowledged',
                'assigned',
                'in_progress',
                'resolved',
                'closed',
                'cancelled'
            ])->default('reported');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution_notes')->nullable();
            $table->decimal('resolution_cost', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['property_id', 'status']);
            $table->index(['tenant_id', 'reported_at']);
            $table->index('report_number');
        });

        // 7. Update subscriptions table
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('custom_product_id')->nullable()->after('tier_id')->constrained('client_custom_products')->onDelete('set null');
            $table->enum('payment_model', ['pay_as_you_go', 'monthly', 'annual', 'hybrid'])->default('monthly')->after('payment_cadence');
        });

        // 8. Update users table
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('requires_subscription')->default(false)->after('email_verified_at');
            $table->enum('account_type', ['client', 'staff', 'tenant'])->default('client')->after('requires_subscription');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['requires_subscription', 'account_type']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['custom_product_id']);
            $table->dropColumn(['custom_product_id', 'payment_model']);
        });

        Schema::dropIfExists('tenant_emergency_reports');
        Schema::dropIfExists('client_custom_products');
        Schema::dropIfExists('product_components');
        Schema::dropIfExists('products');
        Schema::dropIfExists('tenants');

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'property_code',
                'property_brand',
                'has_tenants',
                'number_of_units',
                'tenant_common_password'
            ]);
        });
    }
};
