<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\PropertyStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PropertyStatusTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_property_status()
    {
        $status = PropertyStatus::create([
            'name_ar' => 'متاح',
            'name_en' => 'Available',
            'slug' => 'available',
            'color' => 'green',
            'is_available' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('property_statuses', [
            'name_ar' => 'متاح',
            'name_en' => 'Available',
            'slug' => 'available',
            'color' => 'green',
        ]);
    }

    /** @test */
    public function it_returns_active_statuses()
    {
        PropertyStatus::create([
            'name_ar' => 'متاح',
            'name_en' => 'Available',
            'slug' => 'available',
            'color' => 'green',
            'is_available' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        PropertyStatus::create([
            'name_ar' => 'غير نشط',
            'name_en' => 'Inactive',
            'slug' => 'inactive',
            'color' => 'gray',
            'is_available' => false,
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $activeStatuses = PropertyStatus::active()->get();
        
        $this->assertCount(1, $activeStatuses);
        $this->assertEquals('Available', $activeStatuses->first()->name_en);
    }

    /** @test */
    public function it_checks_availability_for_rent()
    {
        $availableStatus = PropertyStatus::create([
            'name_ar' => 'متاح',
            'name_en' => 'Available',
            'slug' => 'available',
            'color' => 'green',
            'is_available' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $unavailableStatus = PropertyStatus::create([
            'name_ar' => 'مؤجر',
            'name_en' => 'Rented',
            'slug' => 'rented',
            'color' => 'blue',
            'is_available' => false,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->assertTrue($availableStatus->isAvailableForRent());
        $this->assertFalse($unavailableStatus->isAvailableForRent());
    }

    /** @test */
    public function it_validates_status_transitions()
    {
        $availableStatus = PropertyStatus::create([
            'name_ar' => 'متاح',
            'name_en' => 'Available',
            'slug' => 'available',
            'color' => 'green',
            'is_available' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertTrue($availableStatus->canTransitionTo('rented'));
        $this->assertTrue($availableStatus->canTransitionTo('under-maintenance'));
        $this->assertFalse($availableStatus->canTransitionTo('invalid-status'));
    }
}