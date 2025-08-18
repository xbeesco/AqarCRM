<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\UnitFeature;

class UnitFeatureModelTest extends TestCase
{
    public function test_unit_feature_has_required_fields()
    {
        $feature = new UnitFeature([
            'name_ar' => '',
            'name_en' => '',
            'slug' => '',
            'category' => ''
        ]);

        // Basic field presence test
        $this->assertEmpty($feature->name_ar);
        $this->assertEmpty($feature->name_en);
        $this->assertEmpty($feature->slug);
        $this->assertEmpty($feature->category);
    }

    public function test_feature_value_type_validation()
    {
        $feature = new UnitFeature([
            'requires_value' => true,
            'value_type' => 'boolean'
        ]);

        $this->assertTrue($feature->validateValue(true));
        $this->assertTrue($feature->validateValue(false));
        $this->assertTrue($feature->validateValue(1));
        $this->assertTrue($feature->validateValue(0));
    }

    public function test_feature_category_grouping()
    {
        $categories = UnitFeature::getCategoryOptions();
        
        $this->assertIsArray($categories);
        $this->assertArrayHasKey('basic', $categories);
        $this->assertArrayHasKey('amenities', $categories);
        $this->assertArrayHasKey('safety', $categories);
        $this->assertArrayHasKey('luxury', $categories);
        $this->assertArrayHasKey('services', $categories);
    }

    public function test_feature_value_formatting()
    {
        $booleanFeature = new UnitFeature([
            'requires_value' => true,
            'value_type' => 'boolean',
            'name_ar' => 'تكييف',
            'name_en' => 'Air Conditioning'
        ]);

        $this->assertEquals('نعم / Yes', $booleanFeature->getFormattedValueAttribute(true));
        $this->assertEquals('لا / No', $booleanFeature->getFormattedValueAttribute(false));

        $selectFeature = new UnitFeature([
            'requires_value' => true,
            'value_type' => 'select',
            'value_options' => [
                'covered' => 'مغطى / Covered',
                'open' => 'مكشوف / Open'
            ]
        ]);

        $this->assertEquals('مغطى / Covered', $selectFeature->getFormattedValueAttribute('covered'));
    }

    public function test_feature_value_validation_by_type()
    {
        $numberFeature = new UnitFeature([
            'requires_value' => true,
            'value_type' => 'number'
        ]);

        $this->assertTrue($numberFeature->validateValue(5));
        $this->assertTrue($numberFeature->validateValue('10'));
        $this->assertFalse($numberFeature->validateValue('invalid'));

        $textFeature = new UnitFeature([
            'requires_value' => true,
            'value_type' => 'text'
        ]);

        $this->assertTrue($textFeature->validateValue('Some text'));
        $this->assertFalse($textFeature->validateValue(str_repeat('a', 501))); // Too long

        $selectFeature = new UnitFeature([
            'requires_value' => true,
            'value_type' => 'select',
            'value_options' => ['option1' => 'Option 1', 'option2' => 'Option 2']
        ]);

        $this->assertTrue($selectFeature->validateValue('option1'));
        $this->assertFalse($selectFeature->validateValue('invalid_option'));
    }

    public function test_value_type_options()
    {
        $options = UnitFeature::getValueTypeOptions();
        
        $this->assertIsArray($options);
        $this->assertArrayHasKey('boolean', $options);
        $this->assertArrayHasKey('number', $options);
        $this->assertArrayHasKey('text', $options);
        $this->assertArrayHasKey('select', $options);
    }
}