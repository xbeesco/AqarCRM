<?php

namespace Tests\Unit\Models;

use App\Enums\UserType;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    protected function employeeSupportsSoftDeletes(): bool
    {
        return Schema::hasColumn('users', 'deleted_at')
            && in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(Employee::class), true);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create required lookup tables data
        $this->createRequiredLookupData();
    }

    protected function createRequiredLookupData(): void
    {
        // Create property type
        PropertyType::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Apartment',
                'slug' => 'apartment',
            ]
        );

        // Create property status
        PropertyStatus::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Available',
                'slug' => 'available',
            ]
        );

        // Create location
        Location::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Test Location',
                'level' => 1,
            ]
        );
    }

    protected function createEmployee(array $attributes = []): Employee
    {
        $defaults = [
            'name' => 'Test Employee',
            'email' => 'employee'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'phone' => '050'.rand(1000000, 9999999),
        ];

        return Employee::create(array_merge($defaults, $attributes));
    }

    // ==============================================
    // Global Scope Tests
    // ==============================================

    #[Test]
    public function global_scope_filters_only_employee_types(): void
    {
        // Create users of different types using User model
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::SUPER_ADMIN->value,
        ]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::ADMIN->value,
        ]);

        $employee = User::create([
            'name' => 'Employee',
            'email' => 'employee@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::EMPLOYEE->value,
        ]);

        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::OWNER->value,
        ]);

        $tenant = User::create([
            'name' => 'Tenant',
            'email' => 'tenant@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::TENANT->value,
        ]);

        // Query using Employee model - should only return employee types
        $employees = Employee::all();

        $this->assertCount(3, $employees);

        $employeeIds = $employees->pluck('id')->toArray();
        $this->assertContains($superAdmin->id, $employeeIds);
        $this->assertContains($admin->id, $employeeIds);
        $this->assertContains($employee->id, $employeeIds);

        // Should NOT contain owner or tenant
        $this->assertNotContains($owner->id, $employeeIds);
        $this->assertNotContains($tenant->id, $employeeIds);
    }

    #[Test]
    public function global_scope_excludes_owners_and_tenants(): void
    {
        // Create an owner
        User::create([
            'name' => 'Owner',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::OWNER->value,
        ]);

        // Create a tenant
        User::create([
            'name' => 'Tenant',
            'email' => 'tenant@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::TENANT->value,
        ]);

        // Query using Employee model
        $employees = Employee::all();

        // Should be empty since we only created owner and tenant
        $this->assertCount(0, $employees);
    }

    // ==============================================
    // Employee Creation Tests
    // ==============================================

    #[Test]
    public function employee_can_be_created_with_all_fillable_attributes(): void
    {
        $employee = Employee::create([
            'name' => 'John Employee',
            'email' => 'john.employee@example.com',
            'password' => bcrypt('password'),
            'phone' => '0501234567',
            'secondary_phone' => '0509876543',
            'identity_file' => 'path/to/identity.pdf',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'name' => 'John Employee',
            'email' => 'john.employee@example.com',
            'phone' => '0501234567',
            'secondary_phone' => '0509876543',
            'type' => UserType::EMPLOYEE->value,
        ]);
    }

    #[Test]
    public function employee_type_is_auto_set_on_creation(): void
    {
        $employee = Employee::create([
            'name' => 'Auto Type Employee',
            'email' => 'autotype@example.com',
            'password' => bcrypt('password'),
            'phone' => '0501234567',
        ]);

        $this->assertEquals(UserType::EMPLOYEE->value, $employee->type);
    }

    #[Test]
    public function employee_type_is_not_overridden_if_admin(): void
    {
        $employee = Employee::create([
            'name' => 'Admin Employee',
            'email' => 'admin.employee@example.com',
            'password' => bcrypt('password'),
            'phone' => '0501234567',
            'type' => UserType::ADMIN->value,
        ]);

        // Type should remain ADMIN, not be changed to EMPLOYEE
        $this->assertEquals(UserType::ADMIN->value, $employee->type);
    }

    #[Test]
    public function employee_type_is_not_overridden_if_super_admin(): void
    {
        $employee = Employee::create([
            'name' => 'Super Admin Employee',
            'email' => 'superadmin.employee@example.com',
            'password' => bcrypt('password'),
            'phone' => '0501234567',
            'type' => UserType::SUPER_ADMIN->value,
        ]);

        // Type should remain SUPER_ADMIN, not be changed to EMPLOYEE
        $this->assertEquals(UserType::SUPER_ADMIN->value, $employee->type);
    }

    #[Test]
    public function employee_uses_users_table(): void
    {
        $employee = $this->createEmployee();

        $this->assertEquals('users', $employee->getTable());
    }

    // ==============================================
    // Managed Properties Relationship Tests
    // Note: The manager_id column does not exist in the properties table yet.
    // These tests verify the relationship method is defined correctly.
    // ==============================================

    #[Test]
    public function managed_properties_relationship_is_defined(): void
    {
        $employee = $this->createEmployee();

        // Verify the relationship method exists and returns a HasMany relation
        $relation = $employee->managedProperties();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $relation);
    }

    // ==============================================
    // Inheritance from User Tests
    // ==============================================

    #[Test]
    public function employee_inherits_from_user(): void
    {
        $employee = $this->createEmployee();

        $this->assertInstanceOf(User::class, $employee);
    }

    #[Test]
    public function employee_has_hidden_password_attribute(): void
    {
        $employee = $this->createEmployee();

        $this->assertArrayNotHasKey('password', $employee->toArray());
        $this->assertArrayNotHasKey('remember_token', $employee->toArray());
    }

    #[Test]
    public function employee_phone_is_formatted(): void
    {
        $employee = Employee::create([
            'name' => 'Phone Test Employee',
            'email' => 'phonetest@example.com',
            'password' => bcrypt('password'),
            'phone' => '+966-50-123-4567',
        ]);

        $this->assertEquals('966501234567', $employee->phone);
    }

    #[Test]
    public function employee_can_access_panel(): void
    {
        $employee = $this->createEmployee();

        $panel = $this->createMock(\Filament\Panel::class);

        $this->assertTrue($employee->canAccessPanel($panel));
    }

    #[Test]
    public function admin_employee_can_access_panel(): void
    {
        $admin = Employee::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'phone' => '0501234567',
            'type' => UserType::ADMIN->value,
        ]);

        $panel = $this->createMock(\Filament\Panel::class);

        $this->assertTrue($admin->canAccessPanel($panel));
    }

    #[Test]
    public function super_admin_employee_can_access_panel(): void
    {
        $superAdmin = Employee::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'phone' => '0501234567',
            'type' => UserType::SUPER_ADMIN->value,
        ]);

        $panel = $this->createMock(\Filament\Panel::class);

        $this->assertTrue($superAdmin->canAccessPanel($panel));
    }

    // ==============================================
    // User Type Check Methods Tests
    // ==============================================

    #[Test]
    public function is_employee_returns_true_for_employee(): void
    {
        $employee = $this->createEmployee();

        $this->assertTrue($employee->isEmployee());
        $this->assertFalse($employee->isAdmin());
        $this->assertFalse($employee->isSuperAdmin());
        $this->assertFalse($employee->isOwner());
        $this->assertFalse($employee->isTenant());
    }

    #[Test]
    public function is_admin_returns_true_for_admin_employee(): void
    {
        $admin = Employee::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'phone' => '0501234567',
            'type' => UserType::ADMIN->value,
        ]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isEmployee());
        $this->assertFalse($admin->isSuperAdmin());
    }

    #[Test]
    public function is_super_admin_returns_true_for_super_admin_employee(): void
    {
        $superAdmin = Employee::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'phone' => '0501234567',
            'type' => UserType::SUPER_ADMIN->value,
        ]);

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($superAdmin->isAdmin());
        $this->assertFalse($superAdmin->isEmployee());
    }

    // ==============================================
    // Get Type Methods Tests
    // ==============================================

    #[Test]
    public function get_user_type_returns_correct_enum(): void
    {
        $employee = $this->createEmployee();

        $userType = $employee->getUserType();

        $this->assertInstanceOf(UserType::class, $userType);
        $this->assertEquals(UserType::EMPLOYEE, $userType);
    }

    #[Test]
    public function get_type_label_returns_correct_label(): void
    {
        $employee = $this->createEmployee();

        $label = $employee->getTypeLabel();

        $this->assertIsString($label);
        $this->assertNotEmpty($label);
    }

    #[Test]
    public function get_type_color_returns_correct_color(): void
    {
        $employee = $this->createEmployee();

        $color = $employee->getTypeColor();

        $this->assertIsString($color);
        $this->assertNotEmpty($color);
    }
}
