<?php

namespace Tests\Feature;

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
}
