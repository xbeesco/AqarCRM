<?php

namespace Tests\Unit\Policies;

use App\Enums\UserType;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected UserPolicy $policy;

    protected User $superAdmin;

    protected User $admin;

    protected User $employee;

    protected User $owner;

    protected User $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new UserPolicy;

        // Create users with different types
        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::SUPER_ADMIN->value,
        ]);

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::ADMIN->value,
        ]);

        $this->employee = User::create([
            'name' => 'Employee',
            'email' => 'employee@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::EMPLOYEE->value,
        ]);

        $this->owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::OWNER->value,
        ]);

        $this->tenant = User::create([
            'name' => 'Tenant',
            'email' => 'tenant@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::TENANT->value,
        ]);
    }

    // ==================== before() Tests ====================

    #[Test]
    public function before_returns_true_for_super_admin(): void
    {
        $result = $this->policy->before($this->superAdmin, 'viewAny');

        $this->assertTrue($result);
    }

    #[Test]
    public function before_returns_null_for_non_super_admin(): void
    {
        $this->assertNull($this->policy->before($this->admin, 'viewAny'));
        $this->assertNull($this->policy->before($this->employee, 'viewAny'));
        $this->assertNull($this->policy->before($this->owner, 'viewAny'));
        $this->assertNull($this->policy->before($this->tenant, 'viewAny'));
    }

    // ==================== viewAny() Tests ====================

    #[Test]
    public function super_admin_can_view_any_users(): void
    {
        // Super admin bypasses all checks via before()
        $result = $this->policy->before($this->superAdmin, 'viewAny');
        $this->assertTrue($result);
    }

    #[Test]
    public function admin_can_view_any_users(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    #[Test]
    public function employee_cannot_view_any_users(): void
    {
        $this->assertFalse($this->policy->viewAny($this->employee));
    }

    #[Test]
    public function owner_cannot_view_any_users(): void
    {
        $this->assertFalse($this->policy->viewAny($this->owner));
    }

    #[Test]
    public function tenant_cannot_view_any_users(): void
    {
        $this->assertFalse($this->policy->viewAny($this->tenant));
    }

    #[Test]
    public function view_any_logs_unauthorized_access(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Unauthorized access attempt'
                    && $context['user_id'] === $this->employee->id
                    && $context['action'] === 'viewAny';
            });

        $this->policy->viewAny($this->employee);
    }

    // ==================== view() Tests ====================

    #[Test]
    public function super_admin_can_view_any_user(): void
    {
        // Super admin bypasses all checks via before()
        $result = $this->policy->before($this->superAdmin, 'view');
        $this->assertTrue($result);
    }

    #[Test]
    public function admin_can_view_any_user(): void
    {
        $this->assertTrue($this->policy->view($this->admin, $this->employee));
        $this->assertTrue($this->policy->view($this->admin, $this->owner));
        $this->assertTrue($this->policy->view($this->admin, $this->tenant));
    }

    #[Test]
    public function user_can_view_own_profile(): void
    {
        $this->assertTrue($this->policy->view($this->employee, $this->employee));
        $this->assertTrue($this->policy->view($this->owner, $this->owner));
        $this->assertTrue($this->policy->view($this->tenant, $this->tenant));
    }

    #[Test]
    public function employee_cannot_view_other_users(): void
    {
        $this->assertFalse($this->policy->view($this->employee, $this->owner));
        $this->assertFalse($this->policy->view($this->employee, $this->tenant));
    }

    #[Test]
    public function owner_cannot_view_other_users(): void
    {
        $this->assertFalse($this->policy->view($this->owner, $this->employee));
        $this->assertFalse($this->policy->view($this->owner, $this->tenant));
    }

    #[Test]
    public function tenant_cannot_view_other_users(): void
    {
        $this->assertFalse($this->policy->view($this->tenant, $this->employee));
        $this->assertFalse($this->policy->view($this->tenant, $this->owner));
    }

    #[Test]
    public function view_logs_unauthorized_access(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Unauthorized access attempt'
                    && $context['user_id'] === $this->employee->id
                    && $context['action'] === 'view';
            });

        $this->policy->view($this->employee, $this->owner);
    }

    // ==================== create() Tests ====================

    #[Test]
    public function super_admin_can_create_users(): void
    {
        // Super admin bypasses all checks via before()
        $result = $this->policy->before($this->superAdmin, 'create');
        $this->assertTrue($result);
    }

    #[Test]
    public function admin_can_create_users(): void
    {
        $this->assertTrue($this->policy->create($this->admin));
    }

    #[Test]
    public function employee_cannot_create_users(): void
    {
        $this->assertFalse($this->policy->create($this->employee));
    }

    #[Test]
    public function owner_cannot_create_users(): void
    {
        $this->assertFalse($this->policy->create($this->owner));
    }

    #[Test]
    public function tenant_cannot_create_users(): void
    {
        $this->assertFalse($this->policy->create($this->tenant));
    }

    #[Test]
    public function create_logs_unauthorized_access(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Unauthorized access attempt'
                    && $context['user_id'] === $this->employee->id
                    && $context['action'] === 'create';
            });

        $this->policy->create($this->employee);
    }

    // ==================== update() Tests ====================

    #[Test]
    public function super_admin_can_update_any_user(): void
    {
        $this->assertTrue($this->policy->update($this->superAdmin, $this->admin));
        $this->assertTrue($this->policy->update($this->superAdmin, $this->employee));
        $this->assertTrue($this->policy->update($this->superAdmin, $this->owner));
        $this->assertTrue($this->policy->update($this->superAdmin, $this->tenant));
    }

    #[Test]
    public function admin_cannot_update_super_admin(): void
    {
        $this->assertFalse($this->policy->update($this->admin, $this->superAdmin));
    }

    #[Test]
    public function admin_cannot_update_other_admins(): void
    {
        $anotherAdmin = User::create([
            'name' => 'Another Admin',
            'email' => 'anotheradmin@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::ADMIN->value,
        ]);

        $this->assertFalse($this->policy->update($this->admin, $anotherAdmin));
    }

    #[Test]
    public function admin_can_update_employees(): void
    {
        $this->assertTrue($this->policy->update($this->admin, $this->employee));
    }

    #[Test]
    public function admin_can_update_owners(): void
    {
        $this->assertTrue($this->policy->update($this->admin, $this->owner));
    }

    #[Test]
    public function admin_can_update_tenants(): void
    {
        $this->assertTrue($this->policy->update($this->admin, $this->tenant));
    }

    #[Test]
    public function user_can_update_own_profile(): void
    {
        $this->assertTrue($this->policy->update($this->employee, $this->employee));
        $this->assertTrue($this->policy->update($this->owner, $this->owner));
        $this->assertTrue($this->policy->update($this->tenant, $this->tenant));
    }

    #[Test]
    public function employee_cannot_update_other_users(): void
    {
        $this->assertFalse($this->policy->update($this->employee, $this->owner));
    }

    #[Test]
    public function owner_cannot_update_other_users(): void
    {
        $this->assertFalse($this->policy->update($this->owner, $this->employee));
    }

    #[Test]
    public function tenant_cannot_update_other_users(): void
    {
        $this->assertFalse($this->policy->update($this->tenant, $this->employee));
    }

    #[Test]
    public function update_logs_unauthorized_access(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Unauthorized access attempt'
                    && $context['user_id'] === $this->admin->id
                    && $context['action'] === 'update';
            });

        $this->policy->update($this->admin, $this->superAdmin);
    }

    // ==================== delete() Tests ====================

    #[Test]
    public function super_admin_can_delete_any_user_except_themselves(): void
    {
        $this->assertTrue($this->policy->delete($this->superAdmin, $this->admin));
        $this->assertTrue($this->policy->delete($this->superAdmin, $this->employee));
        $this->assertTrue($this->policy->delete($this->superAdmin, $this->owner));
        $this->assertTrue($this->policy->delete($this->superAdmin, $this->tenant));
    }

    #[Test]
    public function super_admin_cannot_delete_themselves(): void
    {
        $this->assertFalse($this->policy->delete($this->superAdmin, $this->superAdmin));
    }

    #[Test]
    public function admin_cannot_delete_super_admin(): void
    {
        $this->assertFalse($this->policy->delete($this->admin, $this->superAdmin));
    }

    #[Test]
    public function admin_cannot_delete_other_admins(): void
    {
        $anotherAdmin = User::create([
            'name' => 'Another Admin',
            'email' => 'anotheradmin2@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::ADMIN->value,
        ]);

        $this->assertFalse($this->policy->delete($this->admin, $anotherAdmin));
    }

    #[Test]
    public function admin_cannot_delete_themselves(): void
    {
        $this->assertFalse($this->policy->delete($this->admin, $this->admin));
    }

    #[Test]
    public function admin_can_delete_employees(): void
    {
        $this->assertTrue($this->policy->delete($this->admin, $this->employee));
    }

    #[Test]
    public function admin_can_delete_owners(): void
    {
        $this->assertTrue($this->policy->delete($this->admin, $this->owner));
    }

    #[Test]
    public function admin_can_delete_tenants(): void
    {
        $this->assertTrue($this->policy->delete($this->admin, $this->tenant));
    }

    #[Test]
    public function employee_cannot_delete_any_user(): void
    {
        $this->assertFalse($this->policy->delete($this->employee, $this->owner));
        $this->assertFalse($this->policy->delete($this->employee, $this->tenant));
    }

    #[Test]
    public function owner_cannot_delete_any_user(): void
    {
        $this->assertFalse($this->policy->delete($this->owner, $this->tenant));
    }

    #[Test]
    public function tenant_cannot_delete_any_user(): void
    {
        $this->assertFalse($this->policy->delete($this->tenant, $this->owner));
    }

    #[Test]
    public function delete_logs_unauthorized_access(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Unauthorized access attempt'
                    && $context['user_id'] === $this->admin->id
                    && $context['action'] === 'delete';
            });

        $this->policy->delete($this->admin, $this->superAdmin);
    }

    // ==================== restore() Tests ====================

    #[Test]
    public function only_super_admin_can_restore_users(): void
    {
        // Super admin bypasses all checks via before()
        $result = $this->policy->before($this->superAdmin, 'restore');
        $this->assertTrue($result);
    }

    #[Test]
    public function admin_cannot_restore_users(): void
    {
        $this->assertFalse($this->policy->restore($this->admin, $this->employee));
    }

    #[Test]
    public function employee_cannot_restore_users(): void
    {
        $this->assertFalse($this->policy->restore($this->employee, $this->owner));
    }

    #[Test]
    public function restore_logs_unauthorized_access(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Unauthorized access attempt'
                    && $context['user_id'] === $this->admin->id
                    && $context['action'] === 'restore';
            });

        $this->policy->restore($this->admin, $this->employee);
    }

    // ==================== forceDelete() Tests ====================

    #[Test]
    public function only_super_admin_can_force_delete(): void
    {
        // Super admin bypasses all checks via before()
        $result = $this->policy->before($this->superAdmin, 'forceDelete');
        $this->assertTrue($result);
    }

    #[Test]
    public function admin_cannot_force_delete(): void
    {
        $this->assertFalse($this->policy->forceDelete($this->admin, $this->employee));
    }

    #[Test]
    public function employee_cannot_force_delete(): void
    {
        $this->assertFalse($this->policy->forceDelete($this->employee, $this->owner));
    }

    #[Test]
    public function force_delete_logs_unauthorized_access(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Unauthorized access attempt'
                    && $context['user_id'] === $this->admin->id
                    && $context['action'] === 'forceDelete';
            });

        $this->policy->forceDelete($this->admin, $this->employee);
    }

    // ==================== changeType() Tests ====================

    #[Test]
    public function only_super_admin_can_change_user_type(): void
    {
        $this->assertTrue($this->policy->changeType($this->superAdmin, $this->employee, 'admin'));
    }

    #[Test]
    public function admin_cannot_change_user_type(): void
    {
        $this->assertFalse($this->policy->changeType($this->admin, $this->employee, 'admin'));
    }

    #[Test]
    public function super_admin_cannot_demote_themselves(): void
    {
        $this->assertFalse($this->policy->changeType($this->superAdmin, $this->superAdmin, 'admin'));
        $this->assertFalse($this->policy->changeType($this->superAdmin, $this->superAdmin, 'employee'));
    }

    #[Test]
    public function super_admin_can_keep_own_type(): void
    {
        $this->assertTrue($this->policy->changeType($this->superAdmin, $this->superAdmin, 'super_admin'));
    }

    #[Test]
    public function change_type_logs_unauthorized_access(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Unauthorized access attempt'
                    && $context['user_id'] === $this->admin->id
                    && $context['action'] === 'changeType';
            });

        $this->policy->changeType($this->admin, $this->employee, 'admin');
    }

    // ==================== viewTrashed() Tests ====================

    #[Test]
    public function only_super_admin_can_view_trashed(): void
    {
        // Super admin bypasses all checks via before()
        $result = $this->policy->before($this->superAdmin, 'viewTrashed');
        $this->assertTrue($result);
    }

    #[Test]
    public function admin_cannot_view_trashed(): void
    {
        $this->assertFalse($this->policy->viewTrashed($this->admin));
    }

    #[Test]
    public function employee_cannot_view_trashed(): void
    {
        $this->assertFalse($this->policy->viewTrashed($this->employee));
    }

    #[Test]
    public function view_trashed_logs_unauthorized_access(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Unauthorized access attempt'
                    && $context['user_id'] === $this->admin->id
                    && $context['action'] === 'viewTrashed';
            });

        $this->policy->viewTrashed($this->admin);
    }
}
