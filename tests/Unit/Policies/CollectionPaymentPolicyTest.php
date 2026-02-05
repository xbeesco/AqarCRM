<?php

namespace Tests\Unit\Policies;

use App\Enums\UserType;
use App\Models\CollectionPayment;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\Setting;
use App\Models\Unit;
use App\Models\UnitContract;
use App\Models\UnitType;
use App\Models\User;
use App\Policies\CollectionPaymentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CollectionPaymentPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected CollectionPaymentPolicy $policy;

    protected User $superAdmin;

    protected User $admin;

    protected User $employee;

    protected User $owner;

    protected User $tenant;

    protected User $anotherTenant;

    protected Property $property;

    protected Unit $unit;

    protected UnitContract $contract;

    protected CollectionPayment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new CollectionPaymentPolicy;

        // Create reference data
        $this->createReferenceData();

        // Create users of different types
        $this->superAdmin = User::factory()->create([
            'type' => UserType::SUPER_ADMIN->value,
            'email' => 'superadmin@test.com',
        ]);

        $this->admin = User::factory()->create([
            'type' => UserType::ADMIN->value,
            'email' => 'admin@test.com',
        ]);

        $this->employee = User::factory()->create([
            'type' => UserType::EMPLOYEE->value,
            'email' => 'employee@test.com',
        ]);

        $this->owner = User::factory()->create([
            'type' => UserType::OWNER->value,
            'email' => 'owner@test.com',
        ]);

        $this->tenant = User::factory()->create([
            'type' => UserType::TENANT->value,
            'email' => 'tenant@test.com',
        ]);

        $this->anotherTenant = User::factory()->create([
            'type' => UserType::TENANT->value,
            'email' => 'another-tenant@test.com',
        ]);

        // Create property owned by the owner
        $this->property = Property::create([
            'name' => 'Test Property',
            'owner_id' => $this->owner->id,
            'location_id' => Location::first()->id,
            'type_id' => PropertyType::first()->id,
            'status_id' => PropertyStatus::first()->id,
            'address' => 'Test Address',
            'postal_code' => '12345',
        ]);

        // Create unit
        $this->unit = Unit::factory()->create([
            'property_id' => $this->property->id,
            'unit_type_id' => UnitType::first()->id,
        ]);

        // Create contract
        $this->contract = UnitContract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'property_id' => $this->property->id,
            'contract_status' => 'draft',
        ]);

        // Create payment
        $this->payment = CollectionPayment::factory()->create([
            'unit_contract_id' => $this->contract->id,
            'unit_id' => $this->unit->id,
            'property_id' => $this->property->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /**
     * Create reference data required for testing.
     */
    protected function createReferenceData(): void
    {
        Location::firstOrCreate(
            ['id' => 1],
            ['name' => 'Default Location', 'level' => 1]
        );

        PropertyType::firstOrCreate(
            ['id' => 1],
            ['name' => 'Apartment', 'slug' => 'apartment']
        );

        PropertyStatus::firstOrCreate(
            ['id' => 1],
            ['name' => 'Available', 'slug' => 'available']
        );

        UnitType::firstOrCreate(
            ['id' => 1],
            ['name' => 'Apartment', 'slug' => 'apartment']
        );

        Setting::set('payment_due_days', 7);
    }

    // ==========================================
    // Super Admin Tests (before method)
    // ==========================================

    #[Test]
    public function super_admin_can_perform_any_action(): void
    {
        $this->assertTrue($this->policy->before($this->superAdmin, 'viewAny'));
        $this->assertTrue($this->policy->before($this->superAdmin, 'view'));
        $this->assertTrue($this->policy->before($this->superAdmin, 'create'));
        $this->assertTrue($this->policy->before($this->superAdmin, 'update'));
        $this->assertTrue($this->policy->before($this->superAdmin, 'delete'));
    }

    #[Test]
    public function before_returns_null_for_non_super_admin(): void
    {
        $this->assertNull($this->policy->before($this->admin, 'viewAny'));
        $this->assertNull($this->policy->before($this->employee, 'viewAny'));
        $this->assertNull($this->policy->before($this->owner, 'viewAny'));
        $this->assertNull($this->policy->before($this->tenant, 'viewAny'));
    }

    // ==========================================
    // viewAny Tests
    // ==========================================

    #[Test]
    public function admin_can_view_any_payments(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    #[Test]
    public function employee_can_view_any_payments(): void
    {
        $this->assertTrue($this->policy->viewAny($this->employee));
    }

    #[Test]
    public function owner_can_view_any_payments(): void
    {
        $this->assertTrue($this->policy->viewAny($this->owner));
    }

    #[Test]
    public function tenant_can_view_any_payments(): void
    {
        $this->assertTrue($this->policy->viewAny($this->tenant));
    }

    // ==========================================
    // view Tests
    // ==========================================

    #[Test]
    public function admin_can_view_any_payment(): void
    {
        $this->assertTrue($this->policy->view($this->admin, $this->payment));
    }

    #[Test]
    public function employee_can_view_any_payment(): void
    {
        $this->assertTrue($this->policy->view($this->employee, $this->payment));
    }

    #[Test]
    public function owner_can_view_payments_for_their_properties(): void
    {
        $this->assertTrue($this->policy->view($this->owner, $this->payment));
    }

    #[Test]
    public function owner_cannot_view_payments_for_other_properties(): void
    {
        // Create another owner with their own property
        $anotherOwner = User::factory()->create([
            'type' => UserType::OWNER->value,
            'email' => 'another-owner@test.com',
        ]);

        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->view($anotherOwner, $this->payment));
    }

    #[Test]
    public function tenant_can_view_own_payments(): void
    {
        $this->assertTrue($this->policy->view($this->tenant, $this->payment));
    }

    #[Test]
    public function tenant_cannot_view_other_tenant_payments(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->view($this->anotherTenant, $this->payment));
    }

    // ==========================================
    // create Tests
    // ==========================================

    #[Test]
    public function admin_can_create_payment(): void
    {
        $this->assertTrue($this->policy->create($this->admin));
    }

    #[Test]
    public function employee_can_create_payment(): void
    {
        $this->assertTrue($this->policy->create($this->employee));
    }

    #[Test]
    public function owner_cannot_create_payment(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->create($this->owner));
    }

    #[Test]
    public function tenant_cannot_create_payment(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->create($this->tenant));
    }

    // ==========================================
    // update Tests
    // ==========================================

    #[Test]
    public function admin_can_update_payment(): void
    {
        $this->assertTrue($this->policy->update($this->admin, $this->payment));
    }

    #[Test]
    public function employee_can_update_payment(): void
    {
        $this->assertTrue($this->policy->update($this->employee, $this->payment));
    }

    #[Test]
    public function owner_cannot_update_payment(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->update($this->owner, $this->payment));
    }

    #[Test]
    public function tenant_cannot_update_payment(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->update($this->tenant, $this->payment));
    }

    // ==========================================
    // delete Tests
    // ==========================================

    #[Test]
    public function admin_can_delete_payment(): void
    {
        $this->assertTrue($this->policy->delete($this->admin, $this->payment));
    }

    #[Test]
    public function employee_cannot_delete_payment(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->delete($this->employee, $this->payment));
    }

    #[Test]
    public function owner_cannot_delete_payment(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->delete($this->owner, $this->payment));
    }

    #[Test]
    public function tenant_cannot_delete_payment(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->delete($this->tenant, $this->payment));
    }

    // ==========================================
    // restore Tests
    // ==========================================

    #[Test]
    public function admin_can_restore_payment(): void
    {
        $this->assertTrue($this->policy->restore($this->admin, $this->payment));
    }

    #[Test]
    public function employee_cannot_restore_payment(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->restore($this->employee, $this->payment));
    }

    #[Test]
    public function owner_cannot_restore_payment(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->restore($this->owner, $this->payment));
    }

    #[Test]
    public function tenant_cannot_restore_payment(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->restore($this->tenant, $this->payment));
    }

    // ==========================================
    // forceDelete Tests
    // ==========================================

    #[Test]
    public function no_one_can_force_delete_payment(): void
    {
        Log::shouldReceive('warning')->times(4);

        $this->assertFalse($this->policy->forceDelete($this->admin, $this->payment));
        $this->assertFalse($this->policy->forceDelete($this->employee, $this->payment));
        $this->assertFalse($this->policy->forceDelete($this->owner, $this->payment));
        $this->assertFalse($this->policy->forceDelete($this->tenant, $this->payment));
    }

    // ==========================================
    // collect Tests
    // ==========================================

    #[Test]
    public function admin_can_collect_payment(): void
    {
        $this->assertTrue($this->policy->collect($this->admin, $this->payment));
    }

    #[Test]
    public function employee_can_collect_payment(): void
    {
        $this->assertTrue($this->policy->collect($this->employee, $this->payment));
    }

    #[Test]
    public function owner_cannot_collect_payment(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->collect($this->owner, $this->payment));
    }

    #[Test]
    public function tenant_cannot_collect_payment(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->collect($this->tenant, $this->payment));
    }
}
