<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckActiveSubscription;
use App\Models\BuildingComponent;
use App\Models\BuildingSubsystem;
use App\Models\BuildingSystem;
use App\Models\FindingTemplateSetting;
use App\Models\RecommendationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BuildingTaxonomyCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $compiledPath = base_path('storage/framework/testing/views');

        if (!is_dir($compiledPath)) {
            mkdir($compiledPath, 0777, true);
        }

        config(['view.compiled' => $compiledPath]);

        $cachePath = new \ReflectionProperty(app('blade.compiler'), 'cachePath');
        $cachePath->setAccessible(true);
        $cachePath->setValue(app('blade.compiler'), $compiledPath);
    }

    public function test_admin_taxonomy_crud_pages_render(): void
    {
        $admin = $this->adminUser();
        [$system, $subsystem] = $this->systemAndSubsystem();
        $component = BuildingComponent::create([
            'building_subsystem_id' => $subsystem->id,
            'code' => 'CRUD-CMP',
            'name' => 'CRUD Component',
            'slug' => 'crud-component',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->get(route('admin.systems.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.systems.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.systems.edit', $system))->assertOk();
        $this->actingAs($admin)->get(route('admin.subsystems.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.subsystems.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.subsystems.edit', $subsystem))->assertOk();
        $this->actingAs($admin)->get(route('admin.components.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.components.create', [
            'building_system_id' => $system->id,
            'building_subsystem_id' => $subsystem->id,
        ]))->assertOk();
        $this->actingAs($admin)->get(route('admin.components.edit', $component))->assertOk();

        FindingTemplateSetting::create([
            'task_question' => 'Visible component finding',
            'building_system_id' => $system->id,
            'building_subsystem_id' => $subsystem->id,
            'building_component_id' => $component->id,
            'default_included' => true,
            'default_recommendations' => ['Repair visible component.'],
            'is_active' => true,
            'sort_order' => 10,
        ]);

        RecommendationSetting::create([
            'recommendation' => 'Visible component recommendation.',
            'building_system_id' => $system->id,
            'building_subsystem_id' => $subsystem->id,
            'building_component_id' => $component->id,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->actingAs($admin)->get(route('admin.finding-template-settings.index', [
            'building_system_id' => $system->id,
            'building_subsystem_id' => $subsystem->id,
            'building_component_id' => $component->id,
        ]))->assertOk()->assertSee('CRUD Component')->assertSee('Visible component finding');

        $this->actingAs($admin)->get(route('admin.recommendation-settings.index', [
            'building_system_id' => $system->id,
            'building_subsystem_id' => $subsystem->id,
            'building_component_id' => $component->id,
        ]))->assertOk()->assertSee('CRUD Component')->assertSee('Visible component recommendation.');

        $this->actingAs($admin)->get(route('admin.finding-template-settings.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.finding-template-settings.edit', FindingTemplateSetting::first()))->assertOk();
        $this->actingAs($admin)->get(route('admin.recommendation-settings.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.recommendation-settings.edit', RecommendationSetting::first()))->assertOk();
    }

    public function test_admin_can_create_update_and_delete_building_system(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('admin.systems.store'), [
                'code' => 'TEST',
                'name' => 'Test System',
                'slug' => 'test-system',
                'description' => 'Temporary taxonomy system.',
                'sort_order' => 130,
                'weight' => 12,
                'is_core' => '1',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.systems.index'));

        $system = BuildingSystem::where('code', 'TEST')->firstOrFail();
        $this->assertSame('Test System', $system->name);
        $this->assertSame(12, $system->weight);
        $this->assertTrue($system->is_core);

        $this->actingAs($admin)
            ->put(route('admin.systems.update', $system), [
                'code' => 'TEST',
                'name' => 'Updated Test System',
                'slug' => 'updated-test-system',
                'sort_order' => 140,
                'weight' => 14,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.systems.index'));

        $this->assertSame('Updated Test System', $system->fresh()->name);
        $this->assertSame(14, $system->fresh()->weight);

        $this->actingAs($admin)
            ->delete(route('admin.systems.destroy', $system->fresh()))
            ->assertRedirect(route('admin.systems.index'));

        $this->assertDatabaseMissing('building_systems', ['code' => 'TEST']);
    }

    public function test_admin_can_create_update_and_delete_building_subsystem(): void
    {
        $admin = $this->adminUser();
        $system = BuildingSystem::create([
            'code' => 'CRUD',
            'name' => 'CRUD System',
            'slug' => 'crud-system',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.subsystems.store'), [
                'building_system_id' => $system->id,
                'code' => 'CRUD-SUB',
                'name' => 'CRUD Subsystem',
                'slug' => 'crud-subsystem',
                'sort_order' => 10,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.subsystems.index'));

        $subsystem = BuildingSubsystem::where('code', 'CRUD-SUB')->firstOrFail();
        $this->assertSame($system->id, $subsystem->building_system_id);

        $this->actingAs($admin)
            ->put(route('admin.subsystems.update', $subsystem), [
                'building_system_id' => $system->id,
                'code' => 'CRUD-SUB',
                'name' => 'Updated CRUD Subsystem',
                'slug' => 'crud-subsystem',
                'sort_order' => 20,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.subsystems.index'));

        $this->assertSame('Updated CRUD Subsystem', $subsystem->fresh()->name);

        $this->actingAs($admin)
            ->delete(route('admin.subsystems.destroy', $subsystem->fresh()))
            ->assertRedirect(route('admin.subsystems.index'));

        $this->assertDatabaseMissing('building_subsystems', ['code' => 'CRUD-SUB']);
    }

    public function test_admin_can_create_update_and_delete_building_component(): void
    {
        $admin = $this->adminUser();
        [$system, $subsystem] = $this->systemAndSubsystem();

        $this->actingAs($admin)
            ->post(route('admin.components.store'), [
                'building_system_id' => $system->id,
                'building_subsystem_id' => $subsystem->id,
                'code' => 'CRUD-CMP',
                'name' => 'CRUD Component',
                'slug' => 'crud-component',
                'default_trade' => 'General',
                'aliases_text' => "Alias One\nAlias Two",
                'sort_order' => 10,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.components.index', ['building_subsystem_id' => $subsystem->id]));

        $component = BuildingComponent::where('code', 'CRUD-CMP')->firstOrFail();
        $this->assertSame($subsystem->id, $component->building_subsystem_id);
        $this->assertSame(['Alias One', 'Alias Two'], $component->aliases);

        $this->actingAs($admin)
            ->put(route('admin.components.update', $component), [
                'building_system_id' => $system->id,
                'building_subsystem_id' => $subsystem->id,
                'code' => 'CRUD-CMP',
                'name' => 'Updated CRUD Component',
                'slug' => 'crud-component',
                'default_trade' => 'Envelope',
                'aliases_text' => 'Alias Three',
                'sort_order' => 20,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.components.index', ['building_subsystem_id' => $subsystem->id]));

        $this->assertSame('Updated CRUD Component', $component->fresh()->name);
        $this->assertSame(['Alias Three'], $component->fresh()->aliases);

        $this->actingAs($admin)
            ->delete(route('admin.components.destroy', $component->fresh()))
            ->assertRedirect(route('admin.components.index', ['building_subsystem_id' => $subsystem->id]));

        $this->assertDatabaseMissing('building_components', ['code' => 'CRUD-CMP']);
    }

    public function test_component_crud_rejects_subsystem_from_another_system(): void
    {
        $admin = $this->adminUser();
        [$system, $subsystem] = $this->systemAndSubsystem();
        $otherSystem = BuildingSystem::create([
            'code' => 'OTHER',
            'name' => 'Other System',
            'slug' => 'other-system',
            'sort_order' => 20,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.components.create'))
            ->post(route('admin.components.store'), [
                'building_system_id' => $otherSystem->id,
                'building_subsystem_id' => $subsystem->id,
                'code' => 'BAD-CMP',
                'name' => 'Wrong Parent Component',
                'slug' => 'wrong-parent-component',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.components.create'))
            ->assertSessionHasErrors('building_subsystem_id');

        $this->assertDatabaseMissing('building_components', ['code' => 'BAD-CMP']);
    }

    public function test_finding_templates_and_recommendations_save_component_scope(): void
    {
        $admin = $this->adminUser();
        [$system, $subsystem] = $this->systemAndSubsystem();
        $component = BuildingComponent::create([
            'building_subsystem_id' => $subsystem->id,
            'code' => 'CRUD-CMP',
            'name' => 'CRUD Component',
            'slug' => 'crud-component',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.finding-template-settings.store'), [
                'task_question' => 'Component-specific finding',
                'building_system_id' => $system->id,
                'building_subsystem_id' => $subsystem->id,
                'building_component_id' => $component->id,
                'category' => 'General',
                'default_included' => '1',
                'default_recommendations' => ['Repair the component.'],
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.finding-template-settings.index'));

        $this->assertSame(
            $component->id,
            FindingTemplateSetting::where('task_question', 'Component-specific finding')->firstOrFail()->building_component_id
        );

        $this->actingAs($admin)
            ->post(route('admin.recommendation-settings.store'), [
                'recommendation' => 'Component-specific recommendation.',
                'building_system_id' => $system->id,
                'building_subsystem_id' => $subsystem->id,
                'building_component_id' => $component->id,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.recommendation-settings.index'));

        $this->assertSame(
            $component->id,
            RecommendationSetting::where('recommendation', 'Component-specific recommendation.')->firstOrFail()->building_component_id
        );
    }

    public function test_finding_templates_and_recommendations_reject_wrong_component_parent(): void
    {
        $admin = $this->adminUser();
        [$system, $subsystem] = $this->systemAndSubsystem();
        $otherSubsystem = BuildingSubsystem::create([
            'building_system_id' => $system->id,
            'code' => 'CRUD-OTHER',
            'name' => 'Other Subsystem',
            'slug' => 'other-subsystem',
            'sort_order' => 20,
            'is_active' => true,
        ]);
        $component = BuildingComponent::create([
            'building_subsystem_id' => $otherSubsystem->id,
            'code' => 'CRUD-OTHER-CMP',
            'name' => 'Other Component',
            'slug' => 'other-component',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.finding-template-settings.create'))
            ->post(route('admin.finding-template-settings.store'), [
                'task_question' => 'Wrong component finding',
                'building_system_id' => $system->id,
                'building_subsystem_id' => $subsystem->id,
                'building_component_id' => $component->id,
                'default_included' => '1',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.finding-template-settings.create'))
            ->assertSessionHasErrors('building_component_id');

        $this->actingAs($admin)
            ->from(route('admin.recommendation-settings.create'))
            ->post(route('admin.recommendation-settings.store'), [
                'recommendation' => 'Wrong component recommendation.',
                'building_system_id' => $system->id,
                'building_subsystem_id' => $subsystem->id,
                'building_component_id' => $component->id,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.recommendation-settings.create'))
            ->assertSessionHasErrors('building_component_id');
    }

    private function adminUser(): User
    {
        $this->withoutMiddleware([CheckActiveSubscription::class]);
        Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('Administrator');

        return $user;
    }

    private function systemAndSubsystem(): array
    {
        $system = BuildingSystem::create([
            'code' => 'CRUD',
            'name' => 'CRUD System',
            'slug' => 'crud-system',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $subsystem = BuildingSubsystem::create([
            'building_system_id' => $system->id,
            'code' => 'CRUD-SUB',
            'name' => 'CRUD Subsystem',
            'slug' => 'crud-subsystem',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        return [$system, $subsystem];
    }
}
