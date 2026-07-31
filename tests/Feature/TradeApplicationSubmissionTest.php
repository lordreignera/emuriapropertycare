<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckActiveSubscription;
use App\Models\BuildingSubsystem;
use App\Models\BuildingSystem;
use App\Models\TradeApplication;
use App\Models\TradePartner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TradeApplicationSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_trade_application_can_be_submitted(): void
    {
        Storage::fake('public');

        $response = $this->post(route('trade-applications.store'), [
            'company_name' => 'Clearline Roofing Ltd',
            'contact_person' => 'Amina Okello',
            'phone' => '0780001111',
            'email' => 'amina@example.test',
            'service_area' => 'Kampala',
            'custom_coverage' => [
                [
                    'system_name' => 'Roofing',
                    'subsystem_name' => 'Metal roof repair',
                    'pricing_unit' => 'pc',
                    'typical_rate' => '12.50',
                    'estimated_hours' => '2.5',
                ],
            ],
            'business_licence_status' => 'pending',
            'liability_insurance_status' => 'pending',
            'worksafebc_status' => 'not_applicable',
            'gst_status' => 'not_applicable',
            'terms_accepted' => '1',
        ]);

        $application = TradeApplication::query()->firstOrFail();

        $response->assertRedirect(route('trade-applications.thank-you', $application));
        $this->assertSame(TradeApplication::STATUS_NEEDS_MORE_INFORMATION, $application->status);
        $this->assertSame('Clearline Roofing Ltd', $application->company_name);
        $this->assertSame('pc', $application->custom_coverage[0]['pricing_unit']);
        $this->assertEquals(2.5, $application->custom_coverage[0]['estimated_hours']);
        $this->assertArrayNotHasKey('maximum_charge', $application->custom_coverage[0]);
        $this->assertArrayNotHasKey('notes', $application->custom_coverage[0]);
    }

    public function test_public_trade_application_can_submit_listed_taxonomy_scope(): void
    {
        Storage::fake('public');

        [$system, $subsystem] = $this->buildingSystemAndSubsystem();

        $response = $this->post(route('trade-applications.store'), array_merge(
            $this->basePayload(),
            [
                'system_ids' => [$system->id],
                'subsystem_ids' => [$subsystem->id],
                'subsystem_pricing' => [
                    $subsystem->id => [
                        'pricing_unit' => 'hr',
                        'typical_rate' => '95.00',
                        'estimated_hours' => '4',
                    ],
                ],
            ]
        ));

        $application = TradeApplication::query()->firstOrFail();

        $response->assertRedirect(route('trade-applications.thank-you', $application));
        $this->assertSame([$system->id], $application->system_ids);
        $this->assertSame([$subsystem->id], $application->subsystem_ids);
        $this->assertEquals(95.0, $application->subsystem_pricing[(string) $subsystem->id]['typical_rate']);
        $this->assertEquals(4.0, $application->subsystem_pricing[(string) $subsystem->id]['estimated_hours']);
        $this->assertArrayNotHasKey('maximum_charge', $application->subsystem_pricing[(string) $subsystem->id]);
    }

    public function test_public_trade_application_rejects_subsystem_from_unselected_system(): void
    {
        Storage::fake('public');

        [$selectedSystem] = $this->buildingSystemAndSubsystem();
        [, $otherSubsystem] = $this->buildingSystemAndSubsystem('OTHER', 'Other System', 'OTHER-SUB', 'Other Subsystem');

        $response = $this
            ->from(route('trade-applications.create'))
            ->post(route('trade-applications.store'), array_merge(
                $this->basePayload(),
                [
                    'system_ids' => [$selectedSystem->id],
                    'subsystem_ids' => [$otherSubsystem->id],
                    'subsystem_pricing' => [
                        $otherSubsystem->id => [
                            'pricing_unit' => 'hr',
                            'typical_rate' => '95.00',
                            'estimated_hours' => '3',
                        ],
                    ],
                ]
            ));

        $response
            ->assertRedirect(route('trade-applications.create'))
            ->assertSessionHasErrors('subsystem_ids');

        $this->assertDatabaseCount('trade_applications', 0);
    }

    public function test_admin_approval_preserves_taxonomy_scope_for_trade_partner(): void
    {
        [$system, $subsystem] = $this->buildingSystemAndSubsystem();
        $admin = $this->adminUser();

        $application = TradeApplication::create(array_merge(
            $this->basePayload(),
            [
                'system_ids' => [$system->id],
                'subsystem_ids' => [$subsystem->id],
                'subsystem_pricing' => [
                    (string) $subsystem->id => [
                        'pricing_unit' => 'hr',
                        'typical_rate' => 95,
                        'estimated_hours' => 4,
                    ],
                ],
                'status' => TradeApplication::STATUS_READY_FOR_REVIEW,
                'submitted_at' => now(),
            ]
        ));

        $this->actingAs($admin)
            ->patch(route('admin.trade-applications.update-status', $application), [
                'status' => TradeApplication::STATUS_APPROVED,
                'admin_notes' => 'Approved for listed scope.',
                'agreed_subsystem_pricing' => [
                    $subsystem->id => [
                        'pricing_unit' => 'hr',
                        'typical_rate' => '110.00',
                        'estimated_hours' => '6',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.trade-partners.index', ['status' => TradePartner::STATUS_ACTIVE]));

        $partner = TradePartner::query()->firstOrFail();

        $this->assertSame([$system->id], $partner->system_ids);
        $this->assertSame([$subsystem->id], $partner->subsystem_ids);
        $this->assertEquals(110.0, $partner->agreed_subsystem_pricing[(string) $subsystem->id]['typical_rate']);
        $this->assertEquals(6.0, $partner->agreed_subsystem_pricing[(string) $subsystem->id]['estimated_hours']);
        $this->assertArrayNotHasKey('maximum_charge', $partner->agreed_subsystem_pricing[(string) $subsystem->id]);
    }

    private function basePayload(): array
    {
        return [
            'company_name' => 'Clearline Roofing Ltd',
            'contact_person' => 'Amina Okello',
            'phone' => '0780001111',
            'email' => 'amina@example.test',
            'service_area' => 'Kampala',
            'business_licence_status' => 'pending',
            'liability_insurance_status' => 'pending',
            'worksafebc_status' => 'not_applicable',
            'gst_status' => 'not_applicable',
            'terms_accepted' => '1',
        ];
    }

    private function adminUser(): User
    {
        $this->withoutMiddleware([CheckActiveSubscription::class]);
        Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('Administrator');

        return $user;
    }

    private function buildingSystemAndSubsystem(
        string $systemCode = 'ENV',
        string $systemName = 'Building Envelope',
        string $subsystemCode = 'ENV-ROOF',
        string $subsystemName = 'Roofing'
    ): array {
        $system = BuildingSystem::create([
            'code' => $systemCode,
            'name' => $systemName,
            'slug' => strtolower(str_replace(' ', '-', $systemName)),
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $subsystem = BuildingSubsystem::create([
            'building_system_id' => $system->id,
            'code' => $subsystemCode,
            'name' => $subsystemName,
            'slug' => strtolower(str_replace(' ', '-', $subsystemName)),
            'sort_order' => 10,
            'is_active' => true,
        ]);

        return [$system, $subsystem];
    }
}
