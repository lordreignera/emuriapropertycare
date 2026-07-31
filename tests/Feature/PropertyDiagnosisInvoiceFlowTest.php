<?php

namespace Tests\Feature;

use App\Models\Inspection;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\User;
use App\Services\InvoicePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PropertyDiagnosisInvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_property_facts_and_diagnosis_invoice_without_upfront_client_payment(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSubscription::class,
        ]);

        Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Client', 'guard_name' => 'web']);

        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $client = User::factory()->create([
            'email' => 'client@example.test',
        ]);
        $client->assignRole('Client');

        $property = Property::create([
            'property_code' => 'TES-3001',
            'user_id' => $client->id,
            'owner_first_name' => 'Demo',
            'owner_phone' => '0780000000',
            'owner_email' => $client->email,
            'property_name' => 'Facts First Home',
            'property_address' => 'Makerere Hill Road',
            'city' => 'Kampala',
            'province' => 'Central',
            'country' => 'Uganda',
            'type' => 'residential',
            'residential_units' => 2,
            'number_of_units' => 2,
            'has_high_pitched_roof' => true,
            'has_crawl_space' => true,
            'status' => 'registered',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('properties.diagnosis-invoice.store', $property), [
                'property_facts_amount' => 120,
                'due_date' => now()->addDays(14)->toDateString(),
            ]);

        $response->assertRedirect();

        $invoice = Invoice::first();
        $this->assertNotNull($invoice);
        $this->assertSame('additional', $invoice->type);
        $this->assertSame('sent', $invoice->status);
        $this->assertSame(843.0, (float) $invoice->total);

        $lineItems = collect($invoice->line_items);
        $this->assertTrue($lineItems->contains(fn ($item) => ($item['purpose'] ?? null) === 'property_facts'));
        $this->assertTrue($lineItems->contains(fn ($item) => ($item['purpose'] ?? null) === 'property_diagnosis'));

        $diagnosisLine = $lineItems->firstWhere('purpose', 'property_diagnosis');
        $this->assertSame(723.0, (float) $diagnosisLine['total']);

        $inspection = Inspection::first();
        $this->assertNotNull($inspection);
        $this->assertSame('pending', $inspection->inspection_fee_status);
        $this->assertSame(723.0, (float) $inspection->inspection_fee_amount);
        $this->assertSame('awaiting_inspection', $property->fresh()->status);
    }

    public function test_invoice_payment_can_be_partial_then_marks_diagnosis_paid_when_settled(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSubscription::class,
        ]);

        Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Client', 'guard_name' => 'web']);

        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $client = User::factory()->create([
            'email' => 'payer@example.test',
        ]);
        $client->assignRole('Client');

        $property = Property::create([
            'property_code' => 'TES-3002',
            'user_id' => $client->id,
            'owner_first_name' => 'Demo',
            'owner_phone' => '0780000001',
            'owner_email' => $client->email,
            'property_name' => 'Payment Plan Home',
            'property_address' => 'Acacia Avenue',
            'city' => 'Kampala',
            'province' => 'Central',
            'country' => 'Uganda',
            'type' => 'residential',
            'residential_units' => 1,
            'number_of_units' => 1,
            'status' => 'registered',
        ]);

        $this->actingAs($admin)
            ->post(route('properties.diagnosis-invoice.store', $property), [
                'property_facts_amount' => 100,
                'due_date' => now()->addDays(14)->toDateString(),
            ])
            ->assertRedirect();

        $invoice = Invoice::firstOrFail();
        $inspection = Inspection::firstOrFail();
        $paymentService = app(InvoicePaymentService::class);

        $paymentService->apply($invoice, 119.70, 'pi_invoice_partial');

        $invoice = $invoice->fresh();
        $this->assertSame('partial', $invoice->status);
        $this->assertSame(119.70, (float) $invoice->paid_amount);
        $this->assertSame('pending', $inspection->fresh()->inspection_fee_status);

        $paymentService->apply($invoice, 279.30, 'pi_invoice_final');

        $invoice = $invoice->fresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertSame(399.0, (float) $invoice->paid_amount);
        $this->assertSame(0.0, (float) $invoice->balance);
        $this->assertSame('paid', $inspection->fresh()->inspection_fee_status);
    }

    public function test_admin_can_preview_and_share_one_live_etogo_property_process_invoice(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSubscription::class,
        ]);

        Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Client', 'guard_name' => 'web']);

        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $client = User::factory()->create([
            'email' => 'invoice-client@example.test',
        ]);
        $client->assignRole('Client');

        $property = Property::create([
            'property_code' => 'TES-3003',
            'user_id' => $client->id,
            'owner_first_name' => 'Invoice Client',
            'owner_phone' => '0780000002',
            'owner_email' => $client->email,
            'property_name' => 'Live Invoice Home',
            'property_address' => '123 Steward Road',
            'city' => 'Toronto',
            'province' => 'Ontario',
            'country' => 'Canada',
            'type' => 'residential',
            'residential_units' => 1,
            'number_of_units' => 1,
            'status' => 'registered',
        ]);

        $this->actingAs($admin)
            ->get(route('properties.process-invoice.preview', $property))
            ->assertOk()
            ->assertSee('ETOGO')
            ->assertSee('Property Registry Creation')
            ->assertSee('admin@etogo.ca');

        $invoice = Invoice::firstOrFail();
        $this->assertSame('draft', $invoice->status);
        $this->assertTrue($invoice->isPropertyProcessInvoice());
        $this->assertSame(['property_registry'], collect($invoice->line_items)->pluck('purpose')->all());
        $this->assertCount(1, Invoice::all());

        $this->actingAs($admin)
            ->get(route('properties.process-invoice.preview', $property))
            ->assertOk();

        $this->assertCount(1, Invoice::all());

        $this->actingAs($client)
            ->get(route('client.invoices.index'))
            ->assertOk()
            ->assertDontSee($invoice->invoice_number);

        $this->actingAs($client)
            ->get(route('client.invoices.show', $invoice))
            ->assertNotFound();

        $this->actingAs($admin)
            ->post(route('properties.process-invoice.share', $property))
            ->assertRedirect(route('invoices.show', $invoice));

        $invoice = $invoice->fresh();
        $this->assertSame('sent', $invoice->status);

        $this->actingAs($client)
            ->get(route('client.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('ETOGO')
            ->assertSee('Koinonia Applied Investments Ltd.')
            ->assertSee('admin@etogo.ca');
    }

    public function test_property_process_preview_reuses_existing_diagnosis_invoice(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSubscription::class,
        ]);

        Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Client', 'guard_name' => 'web']);

        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $client = User::factory()->create([
            'email' => 'existing-invoice@example.test',
        ]);
        $client->assignRole('Client');

        $property = Property::create([
            'property_code' => 'TES-3004',
            'user_id' => $client->id,
            'owner_first_name' => 'Existing',
            'owner_phone' => '0780000003',
            'owner_email' => $client->email,
            'property_name' => 'No Duplicate Invoice Home',
            'property_address' => '456 Billing Lane',
            'city' => 'Toronto',
            'province' => 'Ontario',
            'country' => 'Canada',
            'type' => 'residential',
            'residential_units' => 1,
            'number_of_units' => 1,
            'status' => 'registered',
        ]);

        $this->actingAs($admin)
            ->post(route('properties.diagnosis-invoice.store', $property), [
                'property_facts_amount' => 100,
                'due_date' => now()->addDays(14)->toDateString(),
            ])
            ->assertRedirect();

        $existingInvoice = Invoice::firstOrFail();
        $this->assertTrue($existingInvoice->isPropertyProcessInvoice());

        $this->actingAs($admin)
            ->get(route('properties.process-invoice.preview', $property))
            ->assertOk()
            ->assertSee('Property Registry Creation')
            ->assertSee('Property Diagnosis');

        $this->assertCount(1, Invoice::all());
        $this->assertSame($existingInvoice->id, Invoice::first()->id);
        $this->assertFalse(collect(Invoice::first()->line_items)->pluck('purpose')->contains('property_stewardship'));
    }
}
