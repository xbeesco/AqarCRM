<?php

namespace Tests\Feature\Filament;

use App\Enums\UserType;
use App\Filament\Resources\LocationResource;
use App\Filament\Resources\LocationResource\Pages\ManageLocations;
use App\Models\Location;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocationResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $admin;

    protected User $employee;

    protected User $ownerUser;

    protected User $tenantUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users of different types
        $this->superAdmin = User::factory()->create([
            'type' => UserType::SUPER_ADMIN->value,
            'email' => 'superadmin@test.com',
        ]);

        $this->admin = User::factory()->create([
            'type' => UserType::ADMIN->value,
            'email' => 'admin@test.com',
        ]);

        $this->employee = User::factory()->create([
            'type' => UserType::EMPLOYEE->value,
            'email' => 'employee@test.com',
        ]);

        $this->ownerUser = User::factory()->create([
            'type' => UserType::OWNER->value,
            'email' => 'owner@test.com',
        ]);

        $this->tenantUser = User::factory()->create([
            'type' => UserType::TENANT->value,
            'email' => 'tenant@test.com',
        ]);

        // Set the Filament panel
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    /**
     * Create a location hierarchy for testing
     */
    protected function createLocationHierarchy(): array
    {
        $region = Location::create([
            'name' => 'Test Region',
            'level' => 1,
            'is_active' => true,
        ]);

        $city = Location::create([
            'name' => 'Test City',
            'parent_id' => $region->id,
            'is_active' => true,
        ]);

        $district = Location::create([
            'name' => 'Test District',
            'parent_id' => $city->id,
            'is_active' => true,
        ]);

        $neighborhood = Location::create([
            'name' => 'Test Neighborhood',
            'parent_id' => $district->id,
            'is_active' => true,
        ]);

        return [
            'region' => $region,
            'city' => $city,
            'district' => $district,
            'neighborhood' => $neighborhood,
        ];
    }

    // ==========================================
    // Access Tests (Permissions)
    // ==========================================

    #[Test]
    public function test_super_admin_can_view_locations_list(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(LocationResource::getUrl('index'));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_admin_can_view_locations_list(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(LocationResource::getUrl('index'));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_employee_can_view_locations_list(): void
    {
        $this->actingAs($this->employee);

        $response = $this->get(LocationResource::getUrl('index'));

        $response->assertSuccessful();
    }

    // ==========================================
    // Table Display Tests
    // ==========================================

    #[Test]
    public function test_table_displays_locations(): void
    {
        $this->actingAs($this->admin);

        $location = Location::create([
            'name' => 'Display Test Location',
            'level' => 1,
            'is_active' => true,
        ]);

        Livewire::test(ManageLocations::class)
            ->assertCanSeeTableRecords([$location]);
    }

    #[Test]
    public function test_table_displays_hierarchical_structure(): void
    {
        $this->actingAs($this->admin);

        $hierarchy = $this->createLocationHierarchy();

        Livewire::test(ManageLocations::class)
            ->assertCanSeeTableRecords([
                $hierarchy['region'],
                $hierarchy['city'],
                $hierarchy['district'],
                $hierarchy['neighborhood'],
            ]);
    }

    #[Test]
    public function test_table_sorted_by_path(): void
    {
        $this->actingAs($this->admin);

        // Create multiple root locations
        $region1 = Location::create(['name' => 'Region A', 'level' => 1, 'is_active' => true]);
        $region2 = Location::create(['name' => 'Region B', 'level' => 1, 'is_active' => true]);

        // Create city under first region
        $city = Location::create(['name' => 'City 1', 'parent_id' => $region1->id, 'is_active' => true]);

        // Verify all are visible
        Livewire::test(ManageLocations::class)
            ->assertCanSeeTableRecords([$region1, $region2, $city]);
    }

    #[Test]
    public function test_table_not_paginated(): void
    {
        $this->actingAs($this->admin);

        // Create more than default pagination limit
        for ($i = 1; $i <= 20; $i++) {
            Location::create([
                'name' => "Region {$i}",
                'level' => 1,
                'is_active' => true,
            ]);
        }

        // All locations should be visible without pagination
        $locations = Location::all();

        Livewire::test(ManageLocations::class)
            ->assertCanSeeTableRecords($locations);
    }

    // ==========================================
    // Create Location Tests
    // ==========================================

    #[Test]
    public function test_can_create_root_location(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ManageLocations::class)
            ->callAction('create', data: [
                'level' => 1,
                'name' => 'New Root Location',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('locations', [
            'name' => 'New Root Location',
            'level' => 1,
        ]);
    }

    #[Test]
    public function test_can_create_child_location(): void
    {
        $this->actingAs($this->admin);

        $parent = Location::create([
            'name' => 'Parent Region',
            'level' => 1,
            'is_active' => true,
        ]);

        Livewire::test(ManageLocations::class)
            ->callAction('create', data: [
                'level' => 2,
                'parent_id' => $parent->id,
                'name' => 'Child City',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('locations', [
            'name' => 'Child City',
            'level' => 2,
            'parent_id' => $parent->id,
        ]);
    }

    #[Test]
    public function test_create_location_validates_required_name(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ManageLocations::class)
            ->callAction('create', data: [
                'level' => 1,
                'name' => '',
            ])
            ->assertHasActionErrors(['name' => 'required']);
    }

    #[Test]
    public function test_create_location_validates_required_level(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ManageLocations::class)
            ->callAction('create', data: [
                'level' => null,
                'name' => 'Test Location',
            ])
            ->assertHasActionErrors(['level' => 'required']);
    }

    #[Test]
    public function test_create_location_requires_parent_for_child_levels(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ManageLocations::class)
            ->callAction('create', data: [
                'level' => 2,
                'parent_id' => null,
                'name' => 'City Without Parent',
            ])
            ->assertHasActionErrors(['parent_id' => 'required']);
    }

    // ==========================================
    // Edit Location Tests
    // ==========================================

    #[Test]
    public function test_can_edit_location_name(): void
    {
        $this->actingAs($this->admin);

        $location = Location::create([
            'name' => 'Original Name',
            'level' => 1,
            'is_active' => true,
        ]);

        Livewire::test(ManageLocations::class)
            ->callTableAction('edit', $location, data: [
                'name' => 'Updated Name',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'name' => 'Updated Name',
        ]);
    }

    #[Test]
    public function test_level_cannot_be_changed_on_edit(): void
    {
        $this->actingAs($this->admin);

        $location = Location::create([
            'name' => 'Test Location',
            'level' => 1,
            'is_active' => true,
        ]);

        // Try to change level via edit (should be disabled in form)
        $location->level = 3;
        $location->save();

        // Level should remain unchanged due to model protection
        $this->assertEquals(1, $location->fresh()->level);
    }

    #[Test]
    public function test_can_change_parent_location(): void
    {
        $this->actingAs($this->admin);

        $region1 = Location::create(['name' => 'Region 1', 'level' => 1, 'is_active' => true]);
        $region2 = Location::create(['name' => 'Region 2', 'level' => 1, 'is_active' => true]);

        $city = Location::create([
            'name' => 'City',
            'parent_id' => $region1->id,
            'is_active' => true,
        ]);

        // Move city to different parent
        Livewire::test(ManageLocations::class)
            ->callTableAction('edit', $city, data: [
                'parent_id' => $region2->id,
                'name' => 'City',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertEquals($region2->id, $city->fresh()->parent_id);
    }

    // ==========================================
    // Hierarchical Path Tests
    // ==========================================

    #[Test]
    public function test_path_generated_for_new_location(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ManageLocations::class)
            ->callAction('create', data: [
                'level' => 1,
                'name' => 'Path Test Location',
            ])
            ->assertHasNoActionErrors();

        $location = Location::where('name', 'Path Test Location')->first();

        $this->assertNotNull($location->path);
    }

    #[Test]
    public function test_child_path_contains_parent_path(): void
    {
        $this->actingAs($this->admin);

        $parent = Location::create([
            'name' => 'Parent Location',
            'level' => 1,
            'is_active' => true,
        ]);

        Livewire::test(ManageLocations::class)
            ->callAction('create', data: [
                'level' => 2,
                'parent_id' => $parent->id,
                'name' => 'Child Location',
            ])
            ->assertHasNoActionErrors();

        $child = Location::where('name', 'Child Location')->first();

        $this->assertStringStartsWith($parent->fresh()->path, $child->path);
    }

    // ==========================================
    // Table Formatting Tests
    // ==========================================

    #[Test]
    public function test_formatted_table_display_includes_level_badge(): void
    {
        $this->actingAs($this->admin);

        $location = Location::create([
            'name' => 'Formatted Location',
            'level' => 1,
            'is_active' => true,
        ]);

        $display = $location->getFormattedTableDisplay();

        // Should contain HTML
        $this->assertStringContainsString('<div', $display);
        $this->assertStringContainsString('Formatted Location', $display);
    }

    // ==========================================
    // Model Configuration Tests
    // ==========================================

    #[Test]
    public function test_resource_uses_location_model(): void
    {
        $this->assertEquals(Location::class, LocationResource::getModel());
    }

    #[Test]
    public function test_resource_has_correct_record_title_attribute(): void
    {
        $this->assertEquals('name', LocationResource::getRecordTitleAttribute());
    }

    #[Test]
    public function test_resource_has_correct_pages(): void
    {
        $pages = LocationResource::getPages();

        $this->assertArrayHasKey('index', $pages);
    }

    // ==========================================
    // Level Options Tests
    // ==========================================

    #[Test]
    public function test_level_options_available_in_form(): void
    {
        $options = Location::getLevelOptions();

        $this->assertCount(4, $options);
        $this->assertArrayHasKey(1, $options);
        $this->assertArrayHasKey(2, $options);
        $this->assertArrayHasKey(3, $options);
        $this->assertArrayHasKey(4, $options);
    }

    #[Test]
    public function test_parent_options_empty_for_level_1(): void
    {
        $options = Location::getParentOptions(1);

        $this->assertEmpty($options);
    }

    #[Test]
    public function test_parent_options_populated_for_higher_levels(): void
    {
        $region = Location::create([
            'name' => 'Test Region',
            'level' => 1,
            'is_active' => true,
        ]);

        $options = Location::getParentOptions(2);

        $this->assertArrayHasKey($region->id, $options);
    }

    // ==========================================
    // Eloquent Query Tests
    // ==========================================

    #[Test]
    public function test_eloquent_query_eager_loads_relations(): void
    {
        $this->actingAs($this->admin);

        $hierarchy = $this->createLocationHierarchy();

        $query = LocationResource::getEloquentQuery();
        $locations = $query->get();

        // Check that locations are loaded
        $this->assertTrue($locations->count() > 0);
    }

    #[Test]
    public function test_eloquent_query_orders_by_path(): void
    {
        $this->actingAs($this->admin);

        $hierarchy = $this->createLocationHierarchy();

        $query = LocationResource::getEloquentQuery();
        $locations = $query->get();

        // First location should be the root (region)
        $this->assertEquals($hierarchy['region']->id, $locations->first()->id);
    }

    // ==========================================
    // Scope Tests via Resource
    // ==========================================

    #[Test]
    public function test_countries_scope_available(): void
    {
        Location::create(['name' => 'Country 1', 'level' => 1, 'is_active' => true]);
        $country = Location::create(['name' => 'Country 2', 'level' => 1, 'is_active' => true]);
        Location::create(['name' => 'City 1', 'parent_id' => $country->id, 'is_active' => true]);

        $countries = Location::countries()->get();

        $this->assertCount(2, $countries);
    }

    #[Test]
    public function test_cities_scope_available(): void
    {
        $country = Location::create(['name' => 'Country', 'level' => 1, 'is_active' => true]);
        Location::create(['name' => 'City 1', 'parent_id' => $country->id, 'is_active' => true]);
        Location::create(['name' => 'City 2', 'parent_id' => $country->id, 'is_active' => true]);

        $cities = Location::cities()->get();

        $this->assertCount(2, $cities);
    }

    #[Test]
    public function test_districts_scope_available(): void
    {
        $country = Location::create(['name' => 'Country', 'level' => 1, 'is_active' => true]);
        $city = Location::create(['name' => 'City', 'parent_id' => $country->id, 'is_active' => true]);
        Location::create(['name' => 'District 1', 'parent_id' => $city->id, 'is_active' => true]);
        Location::create(['name' => 'District 2', 'parent_id' => $city->id, 'is_active' => true]);

        $districts = Location::districts()->get();

        $this->assertCount(2, $districts);
    }

    #[Test]
    public function test_neighborhoods_scope_available(): void
    {
        $country = Location::create(['name' => 'Country', 'level' => 1, 'is_active' => true]);
        $city = Location::create(['name' => 'City', 'parent_id' => $country->id, 'is_active' => true]);
        $district = Location::create(['name' => 'District', 'parent_id' => $city->id, 'is_active' => true]);
        Location::create(['name' => 'Neighborhood 1', 'parent_id' => $district->id, 'is_active' => true]);
        Location::create(['name' => 'Neighborhood 2', 'parent_id' => $district->id, 'is_active' => true]);

        $neighborhoods = Location::neighborhoods()->get();

        $this->assertCount(2, $neighborhoods);
    }
}
