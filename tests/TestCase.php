<?php

namespace Tests;

use App\Models\Location;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\UnitCategory;
use App\Models\UnitType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Automatically seed lookup data for tests that use RefreshDatabase
        if (in_array(RefreshDatabase::class, class_uses_recursive($this))) {
            $this->seedLookupData();
        }
    }

    /**
     * Seed required lookup data for tests.
     * This ensures foreign key constraints are satisfied in MySQL.
     */
    protected function seedLookupData(): void
    {
        // Use updateOrInsert to force specific IDs (works with MySQL)
        PropertyType::query()->updateOrInsert(
            ['id' => 1],
            ['name' => 'Apartment', 'slug' => 'apartment', 'created_at' => now(), 'updated_at' => now()]
        );

        PropertyStatus::query()->updateOrInsert(
            ['id' => 1],
            ['name' => 'Available', 'slug' => 'available', 'created_at' => now(), 'updated_at' => now()]
        );

        Location::query()->updateOrInsert(
            ['id' => 1],
            ['name' => 'Test Location', 'level' => 1, 'created_at' => now(), 'updated_at' => now()]
        );

        UnitType::query()->updateOrInsert(
            ['id' => 1],
            ['name' => 'Apartment', 'slug' => 'apartment', 'created_at' => now(), 'updated_at' => now()]
        );

        UnitCategory::query()->updateOrInsert(
            ['id' => 1],
            ['name' => 'Residential', 'slug' => 'residential', 'created_at' => now(), 'updated_at' => now()]
        );
    }
}
