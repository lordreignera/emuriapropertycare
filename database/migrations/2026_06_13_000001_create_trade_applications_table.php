<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number')->unique();
            $table->string('company_name');
            $table->string('contact_person');
            $table->string('phone');
            $table->string('email');
            $table->string('service_area');
            $table->unsignedSmallInteger('years_in_business')->nullable();
            $table->unsignedSmallInteger('technicians_count')->nullable();
            $table->text('company_description')->nullable();
            $table->json('system_ids')->nullable();
            $table->json('subsystem_ids')->nullable();
            $table->json('availability')->nullable();
            $table->string('business_licence_status')->default('pending');
            $table->string('business_licence_number')->nullable();
            $table->date('business_licence_expiry')->nullable();
            $table->string('business_licence_document')->nullable();
            $table->string('liability_insurance_status')->default('pending');
            $table->string('liability_insurance_provider')->nullable();
            $table->string('liability_insurance_policy_number')->nullable();
            $table->date('liability_insurance_expiry')->nullable();
            $table->string('liability_insurance_document')->nullable();
            $table->string('worksafebc_status')->default('pending');
            $table->string('worksafebc_number')->nullable();
            $table->date('worksafebc_expiry')->nullable();
            $table->string('worksafebc_document')->nullable();
            $table->string('gst_status')->default('pending');
            $table->string('gst_number')->nullable();
            $table->string('gst_document')->nullable();
            $table->json('references')->nullable();
            $table->json('additional_documents')->nullable();
            $table->string('status')->default('submitted');
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_applications');
    }
};
