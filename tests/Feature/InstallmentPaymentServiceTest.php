<?php

namespace Tests\Feature;

use App\Models\Inspection;
use App\Models\Property;
use App\Models\User;
use App\Services\InstallmentPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallmentPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_installment_payment_intent_is_applied_once(): void
    {
        $client = User::factory()->create();
        $property = Property::create([
            'property_code' => 'TES-2001',
            'user_id' => $client->id,
            'owner_first_name' => 'Demo',
            'owner_phone' => '0780000000',
            'owner_email' => $client->email,
            'property_name' => 'Installment Test Home',
            'property_address' => 'Makerere Hill Road',
            'city' => 'Kampala',
            'province' => 'Central',
            'country' => 'Uganda',
            'type' => 'residential',
            'residential_units' => 1,
            'status' => 'registered',
        ]);
        $inspection = Inspection::create([
            'property_id' => $property->id,
            'status' => 'completed',
            'payment_plan' => 'per_visit',
            'installment_months' => 3,
            'installments_paid' => 1,
            'installment_amount' => 250,
        ]);

        $service = app(InstallmentPaymentService::class);

        $first = $service->apply($inspection, 'pi_visit_2');
        $duplicate = $service->apply($inspection->fresh(), 'pi_visit_2');
        $second = $service->apply($inspection->fresh(), 'pi_visit_3');

        $this->assertTrue($first['applied']);
        $this->assertFalse($duplicate['applied']);
        $this->assertTrue($second['applied']);

        $inspection = $inspection->fresh();
        $this->assertSame(3, $inspection->installments_paid);
        $this->assertSame(['pi_visit_2', 'pi_visit_3'], $inspection->installment_payment_intent_ids);
        $this->assertNotNull($inspection->arp_fully_paid_at);
    }
}
