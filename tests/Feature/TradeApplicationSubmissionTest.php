<?php

namespace Tests\Feature;

use App\Models\TradeApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
                    'pricing_unit' => 'sf',
                    'typical_rate' => '12.50',
                    'maximum_charge' => '18.00',
                    'estimated_duration' => '2 days',
                    'notes' => 'Materials quoted separately',
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
    }
}
