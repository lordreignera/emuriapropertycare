<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Links to users table for auth
            
            // Client Registration Details (collected during signup)
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('phone');
            $table->string('address');
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Canada');
            
            // Registration Info
            $table->timestamp('registered_at')->nullable();
            $table->enum('account_status', ['pending', 'active', 'suspended', 'cancelled'])->default('pending');
            
            // Subscription Info (denormalized for quick access)
            $table->foreignId('current_subscription_id')->nullable()->constrained('subscriptions')->onDelete('set null');
            $table->foreignId('current_tier_id')->nullable()->constrained('tiers')->onDelete('set null');
            
            // Billing Info
            $table->string('stripe_customer_id')->nullable();
            $table->json('payment_methods')->nullable(); // Store payment method details
            
            // Preferences
            $table->boolean('email_notifications')->default(true);
            $table->boolean('sms_notifications')->default(false);
            $table->string('preferred_contact_method')->default('email'); // email, phone, portal
            
            // Lifetime Value Tracking
            $table->decimal('lifetime_value', 10, 2)->default(0);
            $table->decimal('total_savings', 10, 2)->default(0);
            $table->integer('total_projects')->default(0);
            $table->integer('total_properties')->default(0);
            
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('account_status');
            $table->index('stripe_customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
