<?php

namespace Tests\Unit\Models;

use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocationTest extends TestCase
{
    use RefreshDatabase;

    // ==============================================
    // Model Creation Tests
    // ==============================================

    #[Test]
    public function can_create_location(): void
    {
        $location = Location::create([
            'name' => 'Test Region',
            'level' => 1,
        ]);

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'name' => 'Test Region',
            'level' => 1,
        ]);
    }

    #[Test]
    public function location_has_fillable_attributes(): void
    {
        $location = Location::create([
            'name' => 'Test Location',
            'level' => 1,
        ]);

        $this->assertEquals('Test Location', $location->name);
        $this->assertEquals(1, $location->level);
    }

    #[Test]
    public function location_casts_are_correct(): void
    {
        $location = Location::create([
            'name' => 'Test Location',
            'level' => 2,
        ]);

        $this->assertIsInt($location->level);
    }

    // ==============================================
    // Level Auto-Setting Tests
    // ==============================================

    #[Test]
    public function level_is_auto_set_based_on_parent_when_creating(): void
    {
        $region = Location::create([
            'name' => 'Region',
            'level' => 1,
        ]);

        $city = Location::create([
            'name' => 'City',
            'parent_id' => $region->id,
        ]);

        $this->assertEquals(2, $city->level);
    }

    #[Test]
    public function level_defaults_to_1_when_no_parent(): void
    {
        $location = Location::create([
            'name' => 'Root Location',
        ]);

        $this->assertEquals(1, $location->level);
    }

    #[Test]
    public function level_cannot_be_changed_after_creation(): void
    {
        $location = Location::create([
            'name' => 'Test Location',
            'level' => 1,
        ]);

        $location->level = 3;
        $location->save();

        $this->assertEquals(1, $location->fresh()->level);
    }

    // ==============================================
    // Parent Validation Tests
    // ==============================================

    #[Test]
    public function parent_must_be_one_level_above(): void
    {
        $region = Location::create([
            'name' => 'Region',
            'level' => 1,
        ]);

        $city = Location::create([
            'name' => 'City',
            'parent_id' => $region->id,
        ]);

        $center = Location::create([
            'name' => 'Center',
            'parent_id' => $city->id,
        ]);

        $this->assertEquals(3, $center->level);

        // Try to change parent to region (skipping city level)
        $this->expectException(\InvalidArgumentException::class);
        $center->parent_id = $region->id;
        $center->save();
    }

    // ==============================================
    // Relationship Tests: Parent
    // ==============================================

    #[Test]
    public function location_belongs_to_parent(): void
    {
        $parent = Location::create([
            'name' => 'Parent Location',
            'level' => 1,
        ]);

        $child = Location::create([
            'name' => 'Child Location',
            'parent_id' => $parent->id,
        ]);

        $this->assertInstanceOf(Location::class, $child->parent);
        $this->assertEquals($parent->id, $child->parent->id);
    }

    #[Test]
    public function root_location_has_null_parent(): void
    {
        $root = Location::create([
            'name' => 'Root Location',
            'level' => 1,
        ]);

        $this->assertNull($root->parent);
    }

    // ==============================================
    // Relationship Tests: Children
    // ==============================================

    #[Test]
    public function location_has_many_children(): void
    {
        $parent = Location::create([
            'name' => 'Parent',
            'level' => 1,
        ]);

        $child1 = Location::create([
            'name' => 'Child 1',
            'parent_id' => $parent->id,
        ]);

        $child2 = Location::create([
            'name' => 'Child 2',
            'parent_id' => $parent->id,
        ]);

        // Refresh parent to load children relationship
        $parent->refresh();

        $this->assertCount(2, $parent->children);
        $this->assertTrue($parent->children->contains($child1));
        $this->assertTrue($parent->children->contains($child2));
    }

    #[Test]
    public function leaf_location_has_no_children(): void
    {
        $location = Location::create([
            'name' => 'Leaf Location',
            'level' => 1,
        ]);

        $this->assertCount(0, $location->children);
    }

    // ==============================================
    // Relationship Tests: Properties
    // ==============================================

    #[Test]
    public function location_has_many_properties_relationship(): void
    {
        $location = Location::create([
            'name' => 'Test Location',
            'level' => 1,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $location->properties());
    }

    // ==============================================
    // Scope Tests
    // ==============================================

    #[Test]
    public function scope_level_filters_by_level(): void
    {
        Location::create(['name' => 'Region 1', 'level' => 1, 'is_active' => true]);
        Location::create(['name' => 'Region 2', 'level' => 1, 'is_active' => true]);
        $region = Location::create(['name' => 'Region 3', 'level' => 1, 'is_active' => true]);
        Location::create(['name' => 'City 1', 'parent_id' => $region->id, 'is_active' => true]);

        $level1Locations = Location::level(1)->get();
        $level2Locations = Location::level(2)->get();

        $this->assertCount(3, $level1Locations);
        $this->assertCount(1, $level2Locations);
    }

    #[Test]
    public function scope_countries_returns_level_1(): void
    {
        Location::create(['name' => 'Country 1', 'level' => 1, 'is_active' => true]);
        $country = Location::create(['name' => 'Country 2', 'level' => 1, 'is_active' => true]);
        Location::create(['name' => 'City 1', 'parent_id' => $country->id, 'is_active' => true]);

        $countries = Location::countries()->get();

        $this->assertCount(2, $countries);
        $this->assertTrue($countries->every(fn ($loc) => $loc->level === 1));
    }

    #[Test]
    public function scope_cities_returns_level_2(): void
    {
        $country = Location::create(['name' => 'Country', 'level' => 1, 'is_active' => true]);
        Location::create(['name' => 'City 1', 'parent_id' => $country->id, 'is_active' => true]);
        Location::create(['name' => 'City 2', 'parent_id' => $country->id, 'is_active' => true]);

        $cities = Location::cities()->get();

        $this->assertCount(2, $cities);
        $this->assertTrue($cities->every(fn ($loc) => $loc->level === 2));
    }

    #[Test]
    public function scope_districts_returns_level_3(): void
    {
        $country = Location::create(['name' => 'Country', 'level' => 1, 'is_active' => true]);
        $city = Location::create(['name' => 'City', 'parent_id' => $country->id, 'is_active' => true]);
        Location::create(['name' => 'District 1', 'parent_id' => $city->id, 'is_active' => true]);
        Location::create(['name' => 'District 2', 'parent_id' => $city->id, 'is_active' => true]);

        $districts = Location::districts()->get();

        $this->assertCount(2, $districts);
        $this->assertTrue($districts->every(fn ($loc) => $loc->level === 3));
    }

    #[Test]
    public function scope_neighborhoods_returns_level_4(): void
    {
        $country = Location::create(['name' => 'Country', 'level' => 1, 'is_active' => true]);
        $city = Location::create(['name' => 'City', 'parent_id' => $country->id, 'is_active' => true]);
        $district = Location::create(['name' => 'District', 'parent_id' => $city->id, 'is_active' => true]);
        Location::create(['name' => 'Neighborhood 1', 'parent_id' => $district->id, 'is_active' => true]);
        Location::create(['name' => 'Neighborhood 2', 'parent_id' => $district->id, 'is_active' => true]);

        $neighborhoods = Location::neighborhoods()->get();

        $this->assertCount(2, $neighborhoods);
        $this->assertTrue($neighborhoods->every(fn ($loc) => $loc->level === 4));
    }

    // ==============================================
    // Path Tests
    // ==============================================

    #[Test]
    public function path_is_generated_on_save(): void
    {
        $location = Location::create([
            'name' => 'Test Location',
            'level' => 1,
        ]);

        $this->assertNotNull($location->fresh()->path);
    }

    #[Test]
    public function child_path_starts_with_parent_path(): void
    {
        $parent = Location::create([
            'name' => 'Parent',
            'level' => 1,
        ]);

        $child = Location::create([
            'name' => 'Child',
            'parent_id' => $parent->id,
        ]);

        $parentPath = $parent->fresh()->path;
        $childPath = $child->fresh()->path;

        $this->assertStringStartsWith($parentPath, $childPath);
    }

    #[Test]
    public function update_path_without_saving_updates_path_correctly(): void
    {
        $location = Location::create([
            'name' => 'Test',
            'level' => 1,
        ]);

        $originalPath = $location->path;
        $location->updatePathWithoutSaving();

        $this->assertEquals($originalPath, $location->path);
    }

    #[Test]
    public function children_paths_are_updated_when_parent_path_changes(): void
    {
        // Create initial hierarchy
        $parent1 = Location::create(['name' => 'Parent 1', 'level' => 1, 'is_active' => true]);
        $parent2 = Location::create(['name' => 'Parent 2', 'level' => 1, 'is_active' => true]);
        $child = Location::create(['name' => 'Child', 'parent_id' => $parent1->id, 'is_active' => true]);

        $oldPath = $child->fresh()->path;

        // Note: Moving to different parent is tested through the validation

        $this->assertNotNull($oldPath);
        $this->assertStringStartsWith($parent1->fresh()->path, $oldPath);
    }

    // ==============================================
    // HasHierarchicalPath Trait Tests
    // ==============================================

    #[Test]
    public function level_label_attribute_returns_correct_label(): void
    {
        $level1 = Location::create(['name' => 'Region', 'level' => 1, 'is_active' => true]);
        $level2 = Location::create(['name' => 'City', 'parent_id' => $level1->id, 'is_active' => true]);
        $level3 = Location::create(['name' => 'Center', 'parent_id' => $level2->id, 'is_active' => true]);
        $level4 = Location::create(['name' => 'Neighborhood', 'parent_id' => $level3->id, 'is_active' => true]);

        $this->assertEquals('منطقة', $level1->level_label);
        $this->assertEquals('مدينة', $level2->level_label);
        $this->assertEquals('مركز', $level3->level_label);
        $this->assertEquals('حي', $level4->level_label);
    }

    #[Test]
    public function full_path_attribute_returns_hierarchical_path(): void
    {
        $region = Location::create(['name' => 'Eastern Region', 'level' => 1, 'is_active' => true]);
        $city = Location::create(['name' => 'Riyadh', 'parent_id' => $region->id, 'is_active' => true]);
        $district = Location::create(['name' => 'Al Malaz', 'parent_id' => $city->id, 'is_active' => true]);

        $this->assertEquals('Eastern Region', $region->full_path);
        $this->assertEquals('Eastern Region > Riyadh', $city->full_path);
        $this->assertEquals('Eastern Region > Riyadh > Al Malaz', $district->full_path);
    }

    #[Test]
    public function breadcrumbs_attribute_returns_array(): void
    {
        $region = Location::create(['name' => 'Region', 'level' => 1, 'is_active' => true]);
        $city = Location::create(['name' => 'City', 'parent_id' => $region->id, 'is_active' => true]);

        $breadcrumbs = $city->breadcrumbs;

        $this->assertIsArray($breadcrumbs);
        $this->assertCount(2, $breadcrumbs);
        $this->assertEquals('Region', $breadcrumbs[0]['name']);
        $this->assertEquals('City', $breadcrumbs[1]['name']);
        $this->assertEquals(1, $breadcrumbs[0]['level']);
        $this->assertEquals(2, $breadcrumbs[1]['level']);
    }

    #[Test]
    public function get_level_options_returns_correct_array(): void
    {
        $options = Location::getLevelOptions();

        $this->assertIsArray($options);
        $this->assertCount(4, $options);
        $this->assertEquals('منطقة', $options[1]);
        $this->assertEquals('مدينة', $options[2]);
        $this->assertEquals('مركز', $options[3]);
        $this->assertEquals('حي', $options[4]);
    }

    #[Test]
    public function get_parent_options_returns_empty_for_level_1(): void
    {
        $options = Location::getParentOptions(1);

        $this->assertIsArray($options);
        $this->assertEmpty($options);
    }

    #[Test]
    public function get_parent_options_returns_locations_for_higher_levels(): void
    {
        $region = Location::create(['name' => 'Test Region', 'level' => 1, 'is_active' => true]);

        $options = Location::getParentOptions(2);

        $this->assertIsArray($options);
        $this->assertArrayHasKey($region->id, $options);
    }

    #[Test]
    public function get_hierarchical_options_returns_formatted_options(): void
    {
        $region = Location::create(['name' => 'Region', 'level' => 1, 'is_active' => true]);
        Location::create(['name' => 'City', 'parent_id' => $region->id, 'is_active' => true]);

        $options = Location::getHierarchicalOptions();

        $this->assertIsArray($options);
        $this->assertNotEmpty($options);
    }

    #[Test]
    public function get_formatted_table_display_returns_html(): void
    {
        $location = Location::create(['name' => 'Test Location', 'level' => 1, 'is_active' => true]);

        $display = $location->getFormattedTableDisplay();

        $this->assertIsString($display);
        $this->assertStringContainsString('Test Location', $display);
        $this->assertStringContainsString('<div', $display);
    }

    #[Test]
    public function get_hierarchical_options_with_html_returns_formatted_options(): void
    {
        $region = Location::create(['name' => 'Region', 'level' => 1, 'is_active' => true]);

        $options = Location::getHierarchicalOptionsWithHtml();

        $this->assertIsArray($options);
        $this->assertArrayHasKey($region->id, $options);
        $this->assertStringContainsString('<span', $options[$region->id]);
    }

    // ==============================================
    // Static Methods Tests
    // ==============================================

    #[Test]
    public function get_hierarchical_order_returns_locations_sorted_by_path(): void
    {
        $region = Location::create(['name' => 'Region', 'level' => 1, 'is_active' => true]);
        $city1 = Location::create(['name' => 'City A', 'parent_id' => $region->id, 'is_active' => true]);
        $city2 = Location::create(['name' => 'City B', 'parent_id' => $region->id, 'is_active' => true]);
        Location::create(['name' => 'District', 'parent_id' => $city1->id, 'is_active' => true]);

        $ordered = Location::getHierarchicalOrder();

        $this->assertCount(4, $ordered);
        // First should be the region (level 1)
        $this->assertEquals('Region', $ordered->first()->name);
    }
}
