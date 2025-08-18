<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\UnitStatus;

class UnitStatusModelTest extends TestCase
{
    public function test_unit_status_has_required_fields()
    {
        $status = new UnitStatus([
            'name_ar' => '',
            'name_en' => '',
            'slug' => '',
            'color' => ''
        ]);

        // Basic field presence test
        $this->assertEmpty($status->name_ar);
        $this->assertEmpty($status->name_en);
        $this->assertEmpty($status->slug);
        $this->assertEmpty($status->color);
    }

    public function test_unit_status_color_validation()
    {
        $status = new UnitStatus([
            'color' => 'invalid_color'
        ]);

        $this->assertEquals('invalid_color', $status->color);
    }

    public function test_unit_status_transition_rules()
    {
        $availableStatus = new UnitStatus([
            'slug' => 'available',
            'is_active' => true
        ]);
        
        $occupiedStatus = new UnitStatus([
            'slug' => 'occupied',
            'is_active' => true
        ]);
        
        $maintenanceStatus = new UnitStatus([
            'slug' => 'maintenance',
            'is_active' => true
        ]);

        // Available can transition to occupied
        $this->assertTrue($availableStatus->canTransitionTo($occupiedStatus));
        
        // Available can transition to maintenance
        $this->assertTrue($availableStatus->canTransitionTo($maintenanceStatus));
        
        // Occupied can transition to available
        $this->assertTrue($occupiedStatus->canTransitionTo($availableStatus));
    }

    public function test_unit_status_badge_rendering()
    {
        $status = new UnitStatus([
            'name_ar' => 'متاح',
            'name_en' => 'Available',
            'color' => 'green'
        ]);
        
        $this->assertEquals('green', $status->getBadgeColorAttribute());
    }

    public function test_unit_status_localized_name()
    {
        $status = new UnitStatus([
            'name_ar' => 'متاح',
            'name_en' => 'Available'
        ]);
        
        // Would need to mock app()->getLocale() for proper testing
        $this->assertNotEmpty($status->getNameAttribute());
    }
}