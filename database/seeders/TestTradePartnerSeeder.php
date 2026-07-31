<?php

namespace Database\Seeders;

use App\Models\BuildingSubsystem;
use App\Models\BuildingSystem;
use App\Models\TradeApplication;
use App\Models\TradePartner;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestTradePartnerSeeder extends Seeder
{
    public function run(): void
    {
        $system = BuildingSystem::query()
            ->where('code', 'ENV')
            ->first()
            ?? BuildingSystem::query()->orderBy('sort_order')->first();

        $subsystem = BuildingSubsystem::query()
            ->where('code', 'ENV-ROOF')
            ->first()
            ?? BuildingSubsystem::query()
                ->when($system, fn ($query) => $query->where('building_system_id', $system->id))
                ->orderBy('sort_order')
                ->first();

        if (!$system || !$subsystem) {
            $this->command?->warn('Skipped test trade partner: building taxonomy has not been seeded.');
            return;
        }

        $reviewer = User::query()
            ->role(['Super Admin', 'Administrator'])
            ->first()
            ?? User::query()->first();

        $systemIds = [(int) $system->id];
        $subsystemIds = [(int) $subsystem->id];
        $pricing = [
            (string) $subsystem->id => [
                'pricing_unit' => 'hr',
                'typical_rate' => 95.00,
                'estimated_hours' => 4.0,
            ],
        ];

        $application = TradeApplication::updateOrCreate(
            ['application_number' => 'TA-TEST-ETOGO-ROOFING'],
            [
                'company_name' => 'ETOGO Test Roofing Partner Ltd.',
                'contact_person' => 'Test Partner',
                'phone' => '+1 604 555 0199',
                'email' => 'test.trade.partner@etogo.ca',
                'service_area' => 'Metro Vancouver',
                'years_in_business' => 8,
                'technicians_count' => 6,
                'company_description' => 'Seeded test trade partner for roofing and building-envelope coverage demos.',
                'system_ids' => $systemIds,
                'subsystem_ids' => $subsystemIds,
                'subsystem_pricing' => $pricing,
                'agreed_subsystem_pricing' => $pricing,
                'custom_coverage' => [],
                'agreed_custom_coverage' => [],
                'availability' => ['regular_hours', 'emergency'],
                'minimum_service_charge' => 250.00,
                'emergency_premium' => '1.5x after hours',
                'travel_charge_policy' => 'Included within primary service area',
                'equipment_policy' => 'Standard trade tools included',
                'disposal_policy' => 'Disposal quoted by approved scope when required',
                'standard_warranty' => '1 year labour',
                'business_licence_status' => 'yes',
                'business_licence_number' => 'TEST-BL-1001',
                'liability_insurance_status' => 'yes',
                'liability_insurance_provider' => 'Test Mutual Insurance',
                'liability_insurance_policy_number' => 'TEST-LI-2001',
                'worksafebc_status' => 'yes',
                'worksafebc_number' => 'TEST-WCB-3001',
                'gst_status' => 'yes',
                'gst_number' => 'TEST-GST-4001',
                'references' => [
                    [
                        'name' => 'ETOGO Demo Reference',
                        'phone' => '+1 604 555 0100',
                        'email' => 'reference@etogo.ca',
                    ],
                ],
                'additional_documents' => [],
                'status' => TradeApplication::STATUS_APPROVED,
                'admin_notes' => 'Seeded test partner for local development.',
                'reviewed_by' => $reviewer?->id,
                'submitted_at' => now(),
                'reviewed_at' => now(),
                'pricing_agreed_at' => now(),
            ]
        );

        TradePartner::updateOrCreate(
            ['trade_application_id' => $application->id],
            [
                'partner_number' => 'TP-TEST-ETOGO-ROOFING',
                'company_name' => $application->company_name,
                'contact_person' => $application->contact_person,
                'phone' => $application->phone,
                'email' => $application->email,
                'service_area' => $application->service_area,
                'system_ids' => $systemIds,
                'subsystem_ids' => $subsystemIds,
                'agreed_subsystem_pricing' => $pricing,
                'agreed_custom_coverage' => [],
                'status' => TradePartner::STATUS_ACTIVE,
                'approved_by' => $reviewer?->id,
                'approved_at' => now(),
            ]
        );
    }
}
