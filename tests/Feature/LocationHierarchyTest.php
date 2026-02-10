<?php

namespace Tests\Feature;

use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test hierarchy data
        $this->createTestHierarchy();
    }

    public function test_locations_are_ordered_hierarchically()
    {
        $locations = Location::orderBy('path')->get();

        // Verify the order is maintained
        $names = $locations->pluck('name')->toArray();
        $expectedOrder = [
            'المنطقة الشرقية',
            'مدينة 1',
            'مركز 1-1',
            'حي 1-1-1',
            'مركز 1-2',
            'مدينة 2',
        ];

        $this->assertEquals($expectedOrder, $names, 'Locations should be ordered hierarchically with parents before children');
    }

    public function test_path_generation_maintains_hierarchy()
    {
        $locations = Location::orderBy('path')->get();

        foreach ($locations as $location) {
            if ($location->parent_id) {
                $parent = Location::find($location->parent_id);
                $this->assertNotNull($parent, "Parent should exist for location: {$location->name}");
                $this->assertStringStartsWith(
                    $parent->path,
                    $location->path,
                    "Child path should start with parent path for: {$location->name}"
                );
            }
        }
    }

    public function test_location_levels_are_correct()
    {
        $region = Location::where('name', 'المنطقة الشرقية')->first();
        $city = Location::where('name', 'مدينة 1')->first();
        $center = Location::where('name', 'مركز 1-1')->first();
        $neighborhood = Location::where('name', 'حي 1-1-1')->first();

        $this->assertEquals(1, $region->level, 'Region should be level 1');
        $this->assertEquals(2, $city->level, 'City should be level 2');
        $this->assertEquals(3, $center->level, 'Center should be level 3');
        $this->assertEquals(4, $neighborhood->level, 'Neighborhood should be level 4');
    }

    public function test_path_regeneration_command()
    {
        // Clear all paths
        Location::query()->update(['path' => null]);

        // Run the regeneration command
        $this->artisan('location:regenerate-paths --force')
            ->expectsOutput('Starting location path regeneration...')
            ->expectsOutput('All locations have correct hierarchical paths!')
            ->assertExitCode(0);

        // Verify paths were regenerated correctly
        $locations = Location::whereNotNull('path')->count();
        $this->assertEquals(6, $locations, 'All locations should have regenerated paths');
    }

    private function createTestHierarchy()
    {
        // Create region (level 1)
        $region = Location::create([
            'name' => 'المنطقة الشرقية',
            'level' => 1,
            'is_active' => true,
        ]);

        // Create cities (level 2)
        $city1 = Location::create([
            'name' => 'مدينة 1',
            'parent_id' => $region->id,
            'level' => 2,
            'is_active' => true,
        ]);

        $city2 = Location::create([
            'name' => 'مدينة 2',
            'parent_id' => $region->id,
            'level' => 2,
            'is_active' => true,
        ]);

        // Create centers (level 3)
        $center1 = Location::create([
            'name' => 'مركز 1-1',
            'parent_id' => $city1->id,
            'level' => 3,
            'is_active' => true,
        ]);

        $center2 = Location::create([
            'name' => 'مركز 1-2',
            'parent_id' => $city1->id,
            'level' => 3,
            'is_active' => true,
        ]);

        // Create neighborhood (level 4)
        Location::create([
            'name' => 'حي 1-1-1',
            'parent_id' => $center1->id,
            'level' => 4,
            'is_active' => true,
        ]);
    }
}
