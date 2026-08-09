<?php

namespace Tests\Feature;

use App\Models\Inspection;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientInspectionScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_page_loads_without_precreating_a_stripe_payment_intent(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSubscription::class,
            \Spatie\Permission\Middleware\RoleMiddleware::class,
        ]);

        $user = User::factory()->create([
            'name' => 'Demo Client',
            'email' => 'client@example.test',
        ]);

        $property = Property::create([
            'property_code' => 'TES-1001',
            'user_id' => $user->id,
            'owner_first_name' => 'Demo',
            'owner_phone' => '0780000000',
            'owner_email' => $user->email,
            'property_name' => 'Schedule Test Home',
            'property_address' => 'Makerere Hill Road',
            'city' => 'Kampala',
            'province' => 'Central',
            'country' => 'Uganda',
            'type' => 'residential',
            'residential_units' => 1,
            'status' => 'registered',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->get(route('client.inspections.schedule', $property));

        $response->assertOk();
        $response->assertSee('schedule-payment-page', false);
        $response->assertSee('/client/inspections/' . $property->id . '/payment-intent', false);
        $response->assertDontSee('clientSecret');
    }

    public function test_client_cannot_cancel_another_clients_checkout_inspection(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSubscription::class,
            \Spatie\Permission\Middleware\RoleMiddleware::class,
        ]);

        $client = User::factory()->create();
        $otherClient = User::factory()->create();
        $otherProperty = $this->createClientProperty($otherClient, 'TES-1002');
        $otherInspection = Inspection::create([
            'property_id' => $otherProperty->id,
            'status' => 'scheduled',
            'inspection_fee_status' => 'pending',
        ]);

        $this->actingAs($client, 'sanctum')
            ->get(route('client.inspections.checkout-cancel', ['inspection_id' => $otherInspection->id]))
            ->assertForbidden();

        $this->assertSame('scheduled', $otherInspection->fresh()->status);
    }

    public function test_client_checkout_cancel_only_cancels_own_unpaid_inspection(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSubscription::class,
            \Spatie\Permission\Middleware\RoleMiddleware::class,
        ]);

        $client = User::factory()->create();
        $property = $this->createClientProperty($client, 'TES-1003');
        $unpaidInspection = Inspection::create([
            'property_id' => $property->id,
            'status' => 'scheduled',
            'inspection_fee_status' => 'pending',
        ]);
        $paidInspection = Inspection::create([
            'property_id' => $property->id,
            'status' => 'scheduled',
            'inspection_fee_status' => 'paid',
            'inspection_fee_paid_at' => now(),
        ]);

        $this->actingAs($client, 'sanctum')
            ->get(route('client.inspections.checkout-cancel', ['inspection_id' => $unpaidInspection->id]))
            ->assertRedirect(route('client.properties.index'));

        $this->actingAs($client, 'sanctum')
            ->get(route('client.inspections.checkout-cancel', ['inspection_id' => $paidInspection->id]))
            ->assertRedirect(route('client.properties.index'));

        $this->assertSame('cancelled', $unpaidInspection->fresh()->status);
        $this->assertSame('scheduled', $paidInspection->fresh()->status);
    }

    private function createClientProperty(User $user, string $code): Property
    {
        return Property::create([
            'property_code' => $code,
            'user_id' => $user->id,
            'owner_first_name' => 'Demo',
            'owner_phone' => '0780000000',
            'owner_email' => $user->email,
            'property_name' => 'Schedule Test Home',
            'property_address' => 'Makerere Hill Road',
            'city' => 'Kampala',
            'province' => 'Central',
            'country' => 'Uganda',
            'type' => 'residential',
            'residential_units' => 1,
            'status' => 'registered',
        ]);
    }
}
