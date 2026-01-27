<?php

namespace Tests\Unit\Policies;

use App\Enums\UserType;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyContract;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\User;
use App\Policies\PropertyContractPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PropertyContractPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected PropertyContractPolicy $policy;

    protected User $superAdmin;

    protected User $admin;

    protected User $employee;

    protected User $owner;

    protected User $tenant;

    protected Property $property;

    protected PropertyContract $contract;

    protected PropertyType $propertyType;

    protected PropertyStatus $propertyStatus;

    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new PropertyContractPolicy;

        // Create required reference data
        $this->propertyType = PropertyType::create([
            'name_ar' => 'عمارة',
            'name_en' => 'Building',
            'slug' => 'building',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->propertyStatus = PropertyStatus::create([
            'name_ar' => 'متاح',
            'name_en' => 'Available',
            'slug' => 'available',
            'color' => 'green',
            'is_available' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->location = Location::create([
            'name' => 'الرياض',
            'level' => 1,
            'is_active' => true,
        ]);

        // Create users with different types
        $this->superAdmin = User::factory()->create(['type' => UserType::SUPER_ADMIN->value]);
        $this->admin = User::factory()->create(['type' => UserType::ADMIN->value]);
        $this->employee = User::factory()->create(['type' => UserType::EMPLOYEE->value]);
        $this->owner = User::factory()->create(['type' => UserType::OWNER->value]);
        $this->tenant = User::factory()->create(['type' => UserType::TENANT->value]);

        // Create a property owned by the owner
        $this->property = Property::create([
            'name' => 'Test Property',
            'owner_id' => $this->owner->id,
            'type_id' => $this->propertyType->id,
            'status_id' => $this->propertyStatus->id,
            'location_id' => $this->location->id,
            'address' => '123 Test Street',
        ]);

        // Create a contract for the property without using factory
        $this->contract = PropertyContract::create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'commission_rate' => 5.00,
            'duration_months' => 12,
            'start_date' => now(),
            'end_date' => now()->addMonths(12)->subDay(),
            'contract_status' => 'active',
            'payment_day' => 1,
            'auto_renew' => false,
            'notice_period_days' => 30,
            'payment_frequency' => 'monthly',
            'created_by' => $this->superAdmin->id,
        ]);
    }

    // ==================== viewAny Tests ====================

    public function test_super_admin_can_view_any(): void
    {
        // Super admin is handled by before() method which returns true
        $result = $this->policy->before($this->superAdmin, 'viewAny');
        $this->assertTrue($result);
    }

    public function test_admin_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    public function test_employee_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->employee));
    }

    public function test_owner_can_view_any_with_filter(): void
    {
        // Owner returns true but will be filtered in the query
        $this->assertTrue($this->policy->viewAny($this->owner));
    }

    public function test_tenant_cannot_view_any(): void
    {
        $this->assertFalse($this->policy->viewAny($this->tenant));
    }

    // ==================== view Tests ====================

    public function test_super_admin_can_view_any_contract(): void
    {
        // Super admin bypasses all checks via before()
        $result = $this->policy->before($this->superAdmin, 'view');
        $this->assertTrue($result);
    }

    public function test_admin_can_view_any_contract(): void
    {
        $this->assertTrue($this->policy->view($this->admin, $this->contract));
    }

    public function test_employee_can_view_any_contract(): void
    {
        $this->assertTrue($this->policy->view($this->employee, $this->contract));
    }

    public function test_owner_can_view_own_contract(): void
    {
        $this->assertTrue($this->policy->view($this->owner, $this->contract));
    }

    public function test_owner_cannot_view_other_contract(): void
    {
        // Create another owner
        $otherOwner = User::factory()->create(['type' => UserType::OWNER->value]);

        // Create property for other owner
        $otherProperty = Property::create([
            'name' => 'Other Property',
            'owner_id' => $otherOwner->id,
            'type_id' => $this->propertyType->id,
            'status_id' => $this->propertyStatus->id,
            'location_id' => $this->location->id,
            'address' => '456 Other Street',
        ]);

        // Create contract for other owner - use dates that don't overlap with existing contract
        $otherContract = PropertyContract::create([
            'property_id' => $otherProperty->id,
            'owner_id' => $otherOwner->id,
            'commission_rate' => 5.00,
            'duration_months' => 12,
            'start_date' => now(),
            'end_date' => now()->addMonths(12)->subDay(),
            'contract_status' => 'active',
            'payment_day' => 1,
            'auto_renew' => false,
            'notice_period_days' => 30,
            'payment_frequency' => 'monthly',
            'created_by' => $this->superAdmin->id,
        ]);

        // Original owner should not be able to view other owner's contract
        $this->assertFalse($this->policy->view($this->owner, $otherContract));
    }

    public function test_tenant_cannot_view_contract(): void
    {
        $this->assertFalse($this->policy->view($this->tenant, $this->contract));
    }

    // ==================== create Tests ====================

    public function test_super_admin_can_create(): void
    {
        // Super admin bypasses all checks via before()
        $result = $this->policy->before($this->superAdmin, 'create');
        $this->assertTrue($result);
    }

    public function test_admin_can_create(): void
    {
        $this->assertTrue($this->policy->create($this->admin));
    }

    public function test_employee_cannot_create(): void
    {
        $this->assertFalse($this->policy->create($this->employee));
    }

    public function test_owner_cannot_create(): void
    {
        $this->assertFalse($this->policy->create($this->owner));
    }

    public function test_tenant_cannot_create(): void
    {
        $this->assertFalse($this->policy->create($this->tenant));
    }

    // ==================== update Tests ====================

    public function test_only_super_admin_can_update(): void
    {
        // Super admin bypasses all checks via before()
        $result = $this->policy->before($this->superAdmin, 'update');
        $this->assertTrue($result);
    }

    public function test_admin_cannot_update(): void
    {
        $this->assertFalse($this->policy->update($this->admin, $this->contract));
    }

    public function test_employee_cannot_update(): void
    {
        $this->assertFalse($this->policy->update($this->employee, $this->contract));
    }

    public function test_owner_cannot_update(): void
    {
        $this->assertFalse($this->policy->update($this->owner, $this->contract));
    }

    public function test_update_logs_unauthorized_access(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Unauthorized access attempt'
                    && $context['user_id'] === $this->admin->id
                    && $context['action'] === 'update_property_contract';
            });

        $this->policy->update($this->admin, $this->contract);
    }

    // ==================== delete Tests ====================

    public function test_only_super_admin_can_delete(): void
    {
        // Super admin bypasses all checks via before()
        $result = $this->policy->before($this->superAdmin, 'delete');
        $this->assertTrue($result);
    }

    public function test_admin_cannot_delete(): void
    {
        $this->assertFalse($this->policy->delete($this->admin, $this->contract));
    }

    public function test_employee_cannot_delete(): void
    {
        $this->assertFalse($this->policy->delete($this->employee, $this->contract));
    }

    public function test_owner_cannot_delete(): void
    {
        $this->assertFalse($this->policy->delete($this->owner, $this->contract));
    }

    public function test_delete_logs_unauthorized_access(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Unauthorized access attempt'
                    && $context['user_id'] === $this->admin->id
                    && $context['action'] === 'delete_property_contract';
            });

        $this->policy->delete($this->admin, $this->contract);
    }

    // ==================== terminate Tests ====================

    public function test_super_admin_can_terminate_active_contract(): void
    {
        // Super admin bypasses all checks via before()
        $result = $this->policy->before($this->superAdmin, 'terminate');
        $this->assertTrue($result);
    }

    public function test_admin_can_terminate_active_contract(): void
    {
        // Ensure contract is active
        $this->contract->contract_status = 'active';
        $this->assertTrue($this->policy->terminate($this->admin, $this->contract));
    }

    public function test_cannot_terminate_inactive_contract(): void
    {
        // Set contract status to something other than active
        $this->contract->contract_status = 'draft';
        $this->assertFalse($this->policy->terminate($this->admin, $this->contract));

        $this->contract->contract_status = 'expired';
        $this->assertFalse($this->policy->terminate($this->admin, $this->contract));

        $this->contract->contract_status = 'terminated';
        $this->assertFalse($this->policy->terminate($this->admin, $this->contract));
    }

    public function test_employee_cannot_terminate(): void
    {
        $this->contract->contract_status = 'active';
        $this->assertFalse($this->policy->terminate($this->employee, $this->contract));
    }

    public function test_owner_cannot_terminate(): void
    {
        $this->contract->contract_status = 'active';
        $this->assertFalse($this->policy->terminate($this->owner, $this->contract));
    }

    // ==================== Additional Edge Case Tests ====================

    public function test_restore_is_not_allowed_for_anyone(): void
    {
        // Even admin cannot restore
        $this->assertFalse($this->policy->restore($this->admin, $this->contract));
    }

    public function test_force_delete_is_not_allowed_and_logs(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Unauthorized access attempt'
                    && $context['action'] === 'force_delete_property_contract';
            });

        $this->assertFalse($this->policy->forceDelete($this->admin, $this->contract));
    }

    public function test_before_returns_null_for_non_super_admin(): void
    {
        $this->assertNull($this->policy->before($this->admin, 'viewAny'));
        $this->assertNull($this->policy->before($this->employee, 'viewAny'));
        $this->assertNull($this->policy->before($this->owner, 'viewAny'));
        $this->assertNull($this->policy->before($this->tenant, 'viewAny'));
    }
}
