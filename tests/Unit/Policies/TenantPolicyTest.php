<?php

namespace Tests\Unit\Policies;

use App\Enums\UserType;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\TenantPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected TenantPolicy $policy;

    protected User $superAdmin;

    protected User $admin;

    protected User $employee;

    protected User $owner;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TenantPolicy;

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

        // Create tenant via User model with TENANT type
        $tenantUser = User::factory()->create([
            'type' => UserType::TENANT->value,
            'email' => 'tenant@test.com',
        ]);
        $this->tenant = Tenant::find($tenantUser->id);
    }

    // ==========================================
    // Super Admin Tests (before method)
    // ==========================================

    #[Test]
    public function super_admin_can_perform_any_action(): void
    {
        // Super admin should bypass all checks via before() method
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
    }

    // ==========================================
    // viewAny Tests
    // ==========================================

    #[Test]
    public function admin_can_view_any_tenants(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    #[Test]
    public function employee_can_view_any_tenants(): void
    {
        $this->assertTrue($this->policy->viewAny($this->employee));
    }

    #[Test]
    public function owner_cannot_view_any_tenants(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->viewAny($this->owner));
    }

    #[Test]
    public function tenant_cannot_view_any_tenants(): void
    {
        // Get the tenant as a User model for the policy check
        $tenantAsUser = User::find($this->tenant->id);

        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->viewAny($tenantAsUser));
    }

    // ==========================================
    // view Tests
    // ==========================================

    #[Test]
    public function admin_can_view_any_tenant(): void
    {
        $this->assertTrue($this->policy->view($this->admin, $this->tenant));
    }

    #[Test]
    public function employee_can_view_any_tenant(): void
    {
        $this->assertTrue($this->policy->view($this->employee, $this->tenant));
    }

    #[Test]
    public function tenant_can_view_own_profile(): void
    {
        // Get tenant as User for policy check (same id)
        $tenantAsUser = User::find($this->tenant->id);

        $this->assertTrue($this->policy->view($tenantAsUser, $this->tenant));
    }

    #[Test]
    public function tenant_cannot_view_other_tenant_profile(): void
    {
        // Create another tenant
        $anotherTenantUser = User::factory()->create([
            'type' => UserType::TENANT->value,
            'email' => 'another-tenant@test.com',
        ]);
        $anotherTenant = Tenant::find($anotherTenantUser->id);

        // Get the original tenant as User
        $tenantAsUser = User::find($this->tenant->id);

        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->view($tenantAsUser, $anotherTenant));
    }

    #[Test]
    public function owner_cannot_view_tenant(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->view($this->owner, $this->tenant));
    }

    // ==========================================
    // create Tests
    // ==========================================

    #[Test]
    public function admin_can_create_tenant(): void
    {
        $this->assertTrue($this->policy->create($this->admin));
    }

    #[Test]
    public function employee_can_create_tenant(): void
    {
        $this->assertTrue($this->policy->create($this->employee));
    }

    #[Test]
    public function owner_cannot_create_tenant(): void
    {
        // create() doesn't log - just returns false
        $this->assertFalse($this->policy->create($this->owner));
    }

    #[Test]
    public function tenant_cannot_create_tenant(): void
    {
        $tenantAsUser = User::find($this->tenant->id);

        // create() doesn't log - just returns false
        $this->assertFalse($this->policy->create($tenantAsUser));
    }

    // ==========================================
    // update Tests
    // ==========================================

    #[Test]
    public function admin_can_update_any_tenant(): void
    {
        $this->assertTrue($this->policy->update($this->admin, $this->tenant));
    }

    #[Test]
    public function employee_can_update_tenant(): void
    {
        $this->assertTrue($this->policy->update($this->employee, $this->tenant));
    }

    #[Test]
    public function tenant_can_update_own_profile(): void
    {
        $tenantAsUser = User::find($this->tenant->id);

        $this->assertTrue($this->policy->update($tenantAsUser, $this->tenant));
    }

    #[Test]
    public function tenant_cannot_update_other_tenant_profile(): void
    {
        $anotherTenantUser = User::factory()->create([
            'type' => UserType::TENANT->value,
            'email' => 'another-tenant2@test.com',
        ]);
        $anotherTenant = Tenant::find($anotherTenantUser->id);

        $tenantAsUser = User::find($this->tenant->id);

        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->update($tenantAsUser, $anotherTenant));
    }

    #[Test]
    public function owner_cannot_update_tenant(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->update($this->owner, $this->tenant));
    }

    // ==========================================
    // delete Tests
    // ==========================================

    #[Test]
    public function admin_can_delete_tenant(): void
    {
        $this->assertTrue($this->policy->delete($this->admin, $this->tenant));
    }

    #[Test]
    public function employee_cannot_delete_tenant(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->delete($this->employee, $this->tenant));
    }

    #[Test]
    public function owner_cannot_delete_tenant(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->delete($this->owner, $this->tenant));
    }

    #[Test]
    public function tenant_cannot_delete_any_tenant(): void
    {
        $tenantAsUser = User::find($this->tenant->id);

        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->delete($tenantAsUser, $this->tenant));
    }

    // ==========================================
    // viewFinancialRecords Tests
    // ==========================================

    #[Test]
    public function admin_can_view_any_tenant_financial_records(): void
    {
        $this->assertTrue($this->policy->viewFinancialRecords($this->admin, $this->tenant));
    }

    #[Test]
    public function tenant_can_view_own_financial_records(): void
    {
        $tenantAsUser = User::find($this->tenant->id);

        $this->assertTrue($this->policy->viewFinancialRecords($tenantAsUser, $this->tenant));
    }

    #[Test]
    public function tenant_cannot_view_other_tenant_financial_records(): void
    {
        $anotherTenantUser = User::factory()->create([
            'type' => UserType::TENANT->value,
            'email' => 'another-tenant3@test.com',
        ]);
        $anotherTenant = Tenant::find($anotherTenantUser->id);

        $tenantAsUser = User::find($this->tenant->id);

        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->viewFinancialRecords($tenantAsUser, $anotherTenant));
    }

    #[Test]
    public function employee_cannot_view_tenant_financial_records(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->viewFinancialRecords($this->employee, $this->tenant));
    }

    // ==========================================
    // manageContracts Tests
    // ==========================================

    #[Test]
    public function admin_can_manage_tenant_contracts(): void
    {
        $this->assertTrue($this->policy->manageContracts($this->admin, $this->tenant));
    }

    #[Test]
    public function employee_can_manage_tenant_contracts(): void
    {
        $this->assertTrue($this->policy->manageContracts($this->employee, $this->tenant));
    }

    #[Test]
    public function tenant_cannot_manage_own_contracts(): void
    {
        $tenantAsUser = User::find($this->tenant->id);

        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->manageContracts($tenantAsUser, $this->tenant));
    }

    #[Test]
    public function owner_cannot_manage_tenant_contracts(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->manageContracts($this->owner, $this->tenant));
    }

    // ==========================================
    // makePayments Tests
    // ==========================================

    #[Test]
    public function admin_can_make_payments_for_tenant(): void
    {
        $this->assertTrue($this->policy->makePayments($this->admin, $this->tenant));
    }

    #[Test]
    public function tenant_cannot_make_own_payments(): void
    {
        // Tenants can view but not make/modify payments
        $tenantAsUser = User::find($this->tenant->id);

        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->makePayments($tenantAsUser, $this->tenant));
    }

    #[Test]
    public function employee_cannot_make_payments_for_tenant(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->makePayments($this->employee, $this->tenant));
    }

    #[Test]
    public function owner_cannot_make_payments_for_tenant(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertFalse($this->policy->makePayments($this->owner, $this->tenant));
    }
}
