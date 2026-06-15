<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_trade_pricing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('inspections')->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->foreignId('phar_finding_id')->nullable()->constrained('phar_findings')->nullOnDelete();
            $table->unsignedInteger('finding_index')->nullable();
            $table->foreignId('system_id')->nullable()->constrained('systems')->nullOnDelete();
            $table->foreignId('subsystem_id')->nullable()->constrained('subsystems')->nullOnDelete();
            $table->foreignId('trade_application_id')->nullable()->constrained('trade_applications')->nullOnDelete();
            $table->string('trade_company_name')->nullable();
            $table->string('activity')->nullable();
            $table->string('unit', 30)->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('trade_unit_cost', 10, 2)->default(0);
            $table->decimal('trade_total_cost', 10, 2)->default(0);
            $table->decimal('etogo_client_price', 10, 2)->default(0);
            $table->decimal('etogo_margin_amount', 10, 2)->default(0);
            $table->decimal('margin_rate', 6, 4)->default(0.3500);
            $table->string('pricing_source')->default('default_rule');
            $table->string('approval_status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['inspection_id', 'finding_index']);
            $table->index(['system_id', 'subsystem_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_trade_pricing_items');
    }
};
