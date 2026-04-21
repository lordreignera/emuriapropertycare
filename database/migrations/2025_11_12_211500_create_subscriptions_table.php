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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Client
            $table->foreignId('tier_id')->constrained()->onDelete('restrict');
            // property_id: nullable, no FK (circular dep — property also FKs to subscriptions)
            $table->unsignedBigInteger('property_id')->nullable();
            // custom_product_id: column added here, FK added in create_new_system_tables after client_custom_products exists
            $table->unsignedBigInteger('custom_product_id')->nullable();
            $table->enum('payment_cadence', ['monthly', 'annual'])->default('monthly');
            $table->enum('payment_model', ['pay_as_you_go', 'monthly', 'annual', 'hybrid'])->default('monthly');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_billing_date')->nullable();
            $table->enum('status', ['active', 'expired', 'cancelled', 'paused'])->default('active');
            $table->string('stripe_subscription_id')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
