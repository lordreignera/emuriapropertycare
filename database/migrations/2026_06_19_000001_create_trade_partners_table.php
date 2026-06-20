<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_partners', function (Blueprint $table) {
            $table->id();
            $table->string('partner_number')->unique();
            $table->foreignId('trade_application_id')->unique()->constrained('trade_applications')->cascadeOnDelete();
            $table->string('company_name');
            $table->string('contact_person');
            $table->string('phone');
            $table->string('email');
            $table->string('service_area');
            $table->json('system_ids')->nullable();
            $table->json('subsystem_ids')->nullable();
            $table->json('agreed_subsystem_pricing')->nullable();
            $table->json('agreed_custom_coverage')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        DB::table('trade_applications')
            ->where('status', 'approved')
            ->orderBy('id')
            ->get()
            ->each(function ($application) {
                do {
                    $partnerNumber = 'TP-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
                } while (DB::table('trade_partners')->where('partner_number', $partnerNumber)->exists());

                DB::table('trade_partners')->insert([
                    'partner_number' => $partnerNumber,
                    'trade_application_id' => $application->id,
                    'company_name' => $application->company_name,
                    'contact_person' => $application->contact_person,
                    'phone' => $application->phone,
                    'email' => $application->email,
                    'service_area' => $application->service_area,
                    'system_ids' => $application->system_ids,
                    'subsystem_ids' => $application->subsystem_ids,
                    'agreed_subsystem_pricing' => $application->agreed_subsystem_pricing ?? null,
                    'agreed_custom_coverage' => $application->agreed_custom_coverage ?? null,
                    'status' => 'active',
                    'approved_by' => $application->reviewed_by,
                    'approved_at' => $application->reviewed_at ?? now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_partners');
    }
};
