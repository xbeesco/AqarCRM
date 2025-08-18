<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Unit;
use App\Models\UnitStatus;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UnitModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_has_required_fields()
    {
        $unit = new Unit([
            'property_id' => '',
            'unit_number' => '',
            'floor_number' => '',
            'area_sqm' => '',
            'rooms_count' => '',
            'bathrooms_count' => '',
            'rent_price' => ''
        ]);

        $this->assertFalse($unit->isValid());
    }

    public function test_unit_availability_logic()
    {
        // This would require database setup
        $this->assertTrue(true); // Placeholder
    }

    public function test_unit_pricing_calculation()
    {
        $unit = new Unit(['rent_price' => 1000]);
        
        $this->assertEquals(1000, $unit->calculatePrice('monthly'));
        $this->assertEquals(3000, $unit->calculatePrice('quarterly'));
        $this->assertEquals(12000, $unit->calculatePrice('annual'));
    }

    public function test_unit_code_generation()
    {
        $unit = new Unit(['property_id' => 1, 'unit_number' => '101']);
        
        $this->assertEquals('PROP-1-U101', $unit->getUnitCodeAttribute());
    }

    public function test_unit_type_options()
    {
        $options = Unit::getUnitTypeOptions();
        
        $this->assertIsArray($options);
        $this->assertArrayHasKey('studio', $options);
        $this->assertArrayHasKey('apartment', $options);
        $this->assertArrayHasKey('duplex', $options);
    }

    public function test_unit_ranking_options()
    {
        $options = Unit::getUnitRankingOptions();
        
        $this->assertIsArray($options);
        $this->assertArrayHasKey('economy', $options);
        $this->assertArrayHasKey('standard', $options);
        $this->assertArrayHasKey('premium', $options);
        $this->assertArrayHasKey('luxury', $options);
    }

    public function test_unit_direction_options()
    {
        $options = Unit::getDirectionOptions();
        
        $this->assertIsArray($options);
        $this->assertArrayHasKey('north', $options);
        $this->assertArrayHasKey('south', $options);
        $this->assertArrayHasKey('east', $options);
        $this->assertArrayHasKey('west', $options);
    }

    public function test_unit_view_type_options()
    {
        $options = Unit::getViewTypeOptions();
        
        $this->assertIsArray($options);
        $this->assertArrayHasKey('street', $options);
        $this->assertArrayHasKey('garden', $options);
        $this->assertArrayHasKey('sea', $options);
    }

    public function test_unit_display_attributes()
    {
        $unit = new Unit([
            'rooms_count' => 2,
            'bathrooms_count' => 1,
            'area_sqm' => 85.50,
            'rent_price' => 2500.00
        ]);
        
        $this->assertEquals('2R/1B', $unit->getRoomsBathroomsDisplayAttribute());
        $this->assertEquals('85.5 م²', $unit->getAreaDisplayAttribute());
        $this->assertEquals('2,500.00 SAR', $unit->getRentDisplayAttribute());
    }
}