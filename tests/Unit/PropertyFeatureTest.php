<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\PropertyFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PropertyFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_property_feature()
    {
        $feature = PropertyFeature::create([
            'name_ar' => 'مصعد',
            'name_en' => 'Elevator',
            'slug' => 'elevator',
            'category' => 'basics',
            'value_type' => 'boolean',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('property_features', [
            'name_ar' => 'مصعد',
            'name_en' => 'Elevator',
            'slug' => 'elevator',
            'category' => 'basics',
        ]);
    }

    /** @test */
    public function it_validates_value_types()
    {
        $booleanFeature = PropertyFeature::create([
            'name_ar' => 'مصعد',
            'name_en' => 'Elevator',
            'slug' => 'elevator',
            'category' => 'basics',
            'value_type' => 'boolean',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $numberFeature = PropertyFeature::create([
            'name_ar' => 'موقف سيارات',
            'name_en' => 'Parking',
            'slug' => 'parking',
            'category' => 'basics',
            'value_type' => 'number',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->assertTrue($booleanFeature->isValidValue(true));
        $this->assertTrue($booleanFeature->isValidValue(false));
        $this->assertFalse($booleanFeature->isValidValue('invalid'));

        $this->assertTrue($numberFeature->isValidValue(5));
        $this->assertTrue($numberFeature->isValidValue('10'));
        $this->assertFalse($numberFeature->isValidValue('not-a-number'));
    }

    /** @test */
    public function it_formats_values_correctly()
    {
        $booleanFeature = PropertyFeature::create([
            'name_ar' => 'مصعد',
            'name_en' => 'Elevator',
            'slug' => 'elevator',
            'category' => 'basics',
            'value_type' => 'boolean',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $numberFeature = PropertyFeature::create([
            'name_ar' => 'موقف سيارات',
            'name_en' => 'Parking',
            'slug' => 'parking',
            'category' => 'basics',
            'value_type' => 'number',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->assertEquals(true, $booleanFeature->getFormattedValue(1));
        $this->assertEquals(false, $booleanFeature->getFormattedValue(0));
        
        $this->assertEquals(5.0, $numberFeature->getFormattedValue('5'));
        $this->assertEquals(10.5, $numberFeature->getFormattedValue('10.5'));
    }

    /** @test */
    public function it_filters_by_category()
    {
        PropertyFeature::create([
            'name_ar' => 'مصعد',
            'name_en' => 'Elevator',
            'slug' => 'elevator',
            'category' => 'basics',
            'value_type' => 'boolean',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        PropertyFeature::create([
            'name_ar' => 'مسبح',
            'name_en' => 'Swimming Pool',
            'slug' => 'swimming-pool',
            'category' => 'amenities',
            'value_type' => 'boolean',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $basicFeatures = PropertyFeature::byCategory('basics')->get();
        $amenityFeatures = PropertyFeature::byCategory('amenities')->get();
        
        $this->assertCount(1, $basicFeatures);
        $this->assertCount(1, $amenityFeatures);
        $this->assertEquals('Elevator', $basicFeatures->first()->name_en);
        $this->assertEquals('Swimming Pool', $amenityFeatures->first()->name_en);
    }
}