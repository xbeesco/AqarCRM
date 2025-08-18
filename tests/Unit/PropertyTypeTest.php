<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\PropertyType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PropertyTypeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_property_type()
    {
        $propertyType = PropertyType::create([
            'name_ar' => 'فيلا',
            'name_en' => 'Villa',
            'slug' => 'villa',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('property_types', [
            'name_ar' => 'فيلا',
            'name_en' => 'Villa',
            'slug' => 'villa',
        ]);
    }

    /** @test */
    public function it_generates_slug_from_name_en()
    {
        $propertyType = PropertyType::create([
            'name_ar' => 'شقة',
            'name_en' => 'Apartment',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertEquals('apartment', $propertyType->slug);
    }

    /** @test */
    public function it_returns_active_property_types()
    {
        PropertyType::create([
            'name_ar' => 'فيلا',
            'name_en' => 'Villa',
            'slug' => 'villa',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        PropertyType::create([
            'name_ar' => 'شقة',
            'name_en' => 'Apartment',
            'slug' => 'apartment',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $activeTypes = PropertyType::active()->get();
        
        $this->assertCount(1, $activeTypes);
        $this->assertEquals('Villa', $activeTypes->first()->name_en);
    }

    /** @test */
    public function it_can_have_parent_child_relationships()
    {
        $parent = PropertyType::create([
            'name_ar' => 'عقار سكني',
            'name_en' => 'Residential Property',
            'slug' => 'residential-property',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $child = PropertyType::create([
            'name_ar' => 'فيلا',
            'name_en' => 'Villa',
            'slug' => 'villa',
            'parent_id' => $parent->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertTrue($parent->hasChildren());
        $this->assertEquals($parent->id, $child->parent->id);
        $this->assertEquals('Residential Property > Villa', $child->full_path);
    }
}