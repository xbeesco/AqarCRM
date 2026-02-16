<?php

namespace Tests\Feature\Filament;

use App\Enums\UserType;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Models\Employee;
use App\Models\Location;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmployeeResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $admin;

    protected User $employee;

    protected User $ownerUser;

    protected User $tenantUser;

    protected bool $isUsingSqlite = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Check if we're using SQLite
        $this->isUsingSqlite = DB::connection()->getDriverName() === 'sqlite';

        // Create required reference data
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

        $this->ownerUser = User::factory()->create([
            'type' => UserType::OWNER->value,
            'email' => 'owner@test.com',
        ]);

        $this->tenantUser = User::factory()->create([
            'type' => UserType::TENANT->value,
            'email' => 'tenant@test.com',
        ]);

        // Set the Filament panel
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    /**
     * Create reference data required for testing
     */
    protected function createReferenceData(): void
    {
        // Create default Location
        Location::firstOrCreate(
            ['id' => 1],
            ['name' => 'Default Location', 'level' => 1]
        );

        // Create default PropertyType
        PropertyType::firstOrCreate(
            ['id' => 1],
            ['name' => 'Apartment', 'slug' => 'apartment']
        );

        // Create default PropertyStatus
        PropertyStatus::firstOrCreate(
            ['id' => 1],
            ['name' => 'Available', 'slug' => 'available']
        );
    }

    /**
     * Create an employee for testing
     */
    protected function createTestEmployee(array $attributes = []): Employee
    {
        $defaults = [
            'name' => 'Test Employee',
            'email' => 'testemployee'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'phone' => '050'.rand(1000000, 9999999),
        ];

        return Employee::create(array_merge($defaults, $attributes));
    }

    // ==========================================
    // Access Tests (Permissions)
    // ==========================================

    #[Test]
    public function test_super_admin_can_view_employees_list(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(EmployeeResource::getUrl('index'));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_admin_can_view_employees_list(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(EmployeeResource::getUrl('index'));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_employee_cannot_view_employees_list(): void
    {
        // Note: Current code only allows super_admin and admin to view employees list
        $this->actingAs($this->employee);

        $response = $this->get(EmployeeResource::getUrl('index'));

        $response->assertStatus(403);
    }

    #[Test]
    public function test_employee_cannot_view_any(): void
    {
        // Note: Current code only allows super_admin and admin to view any employees
        $this->actingAs($this->employee);

        $this->assertFalse(EmployeeResource::canViewAny());
    }

    #[Test]
    public function test_owner_cannot_view_employees_list(): void
    {
        $this->actingAs($this->ownerUser);

        $response = $this->get(EmployeeResource::getUrl('index'));

        // Owner should be forbidden based on canViewAny
        $response->assertStatus(403);
    }

    #[Test]
    public function test_tenant_cannot_view_employees_list(): void
    {
        $this->actingAs($this->tenantUser);

        $response = $this->get(EmployeeResource::getUrl('index'));

        // Tenant should be forbidden based on canViewAny
        $response->assertStatus(403);
    }

    // ==========================================
    // canViewAny Permission Tests
    // ==========================================

    #[Test]
    public function test_can_view_any_returns_true_for_admins_only(): void
    {
        // Note: Current code only allows super_admin and admin (not employee)
        // Test super_admin
        $this->actingAs($this->superAdmin);
        $this->assertTrue(EmployeeResource::canViewAny());

        // Test admin
        $this->actingAs($this->admin);
        $this->assertTrue(EmployeeResource::canViewAny());

        // Test employee - NOT allowed
        $this->actingAs($this->employee);
        $this->assertFalse(EmployeeResource::canViewAny());
    }

    #[Test]
    public function test_can_view_any_returns_false_for_clients(): void
    {
        // Test owner
        $this->actingAs($this->ownerUser);
        $this->assertFalse(EmployeeResource::canViewAny());

        // Test tenant
        $this->actingAs($this->tenantUser);
        $this->assertFalse(EmployeeResource::canViewAny());
    }

    // ==========================================
    // canCreate Permission Tests
    // ==========================================

    #[Test]
    public function test_super_admin_can_create_employee(): void
    {
        $this->actingAs($this->superAdmin);

        $this->assertTrue(EmployeeResource::canCreate());
    }

    #[Test]
    public function test_admin_can_create_employee(): void
    {
        $this->actingAs($this->admin);

        $this->assertTrue(EmployeeResource::canCreate());
    }

    #[Test]
    public function test_employee_cannot_create_employee(): void
    {
        // Note: Current code only allows super_admin and admin to create employees
        $this->actingAs($this->employee);

        $this->assertFalse(EmployeeResource::canCreate());
    }

    // ==========================================
    // canEdit Permission Tests
    // ==========================================

    #[Test]
    public function test_super_admin_can_edit_employee(): void
    {
        $employee = $this->createTestEmployee();

        $this->actingAs($this->superAdmin);

        $this->assertTrue(EmployeeResource::canEdit($employee));
    }

    #[Test]
    public function test_admin_can_edit_employee(): void
    {
        $employee = $this->createTestEmployee();

        $this->actingAs($this->admin);

        $this->assertTrue(EmployeeResource::canEdit($employee));
    }

    #[Test]
    public function test_employee_cannot_edit_employee(): void
    {
        $employee = $this->createTestEmployee();

        $this->actingAs($this->employee);

        $this->assertFalse(EmployeeResource::canEdit($employee));
    }

    // ==========================================
    // canDelete Permission Tests
    // ==========================================

    #[Test]
    public function test_only_super_admin_can_delete_employee(): void
    {
        $employee = $this->createTestEmployee();

        // Super admin can delete
        $this->actingAs($this->superAdmin);
        $this->assertTrue(EmployeeResource::canDelete($employee));

        // Admin cannot delete
        $this->actingAs($this->admin);
        $this->assertFalse(EmployeeResource::canDelete($employee));

        // Employee cannot delete
        $this->actingAs($this->employee);
        $this->assertFalse(EmployeeResource::canDelete($employee));
    }

    #[Test]
    public function test_can_delete_any_returns_correct_permission(): void
    {
        // Super admin can delete any
        $this->actingAs($this->superAdmin);
        $this->assertTrue(EmployeeResource::canDeleteAny());

        // Admin cannot delete any
        $this->actingAs($this->admin);
        $this->assertFalse(EmployeeResource::canDeleteAny());
    }

    // ==========================================
    // Table Tests
    // ==========================================

    #[Test]
    public function test_table_displays_employees(): void
    {
        $this->actingAs($this->superAdmin);

        $employee = $this->createTestEmployee(['name' => 'Display Test Employee']);

        Livewire::test(ListEmployees::class)
            ->assertCanSeeTableRecords([$employee]);
    }

    #[Test]
    public function test_table_search_by_name_works(): void
    {
        $this->actingAs($this->superAdmin);

        $employee1 = $this->createTestEmployee(['name' => 'Unique Searchable Employee']);
        $employee2 = $this->createTestEmployee(['name' => 'Another Employee']);

        Livewire::test(ListEmployees::class)
            ->searchTable('Unique Searchable')
            ->assertCanSeeTableRecords([$employee1])
            ->assertCanNotSeeTableRecords([$employee2]);
    }

    #[Test]
    public function test_table_search_by_email_works(): void
    {
        $this->actingAs($this->superAdmin);

        $employee1 = $this->createTestEmployee([
            'name' => 'Email Employee 1',
            'email' => 'unique.employee@test.com',
        ]);
        $employee2 = $this->createTestEmployee([
            'name' => 'Email Employee 2',
            'email' => 'another.employee@test.com',
        ]);

        Livewire::test(ListEmployees::class)
            ->searchTable('unique.employee')
            ->assertCanSeeTableRecords([$employee1])
            ->assertCanNotSeeTableRecords([$employee2]);
    }

    #[Test]
    public function test_table_search_by_phone_works(): void
    {
        $this->actingAs($this->superAdmin);

        $employee1 = $this->createTestEmployee([
            'name' => 'Phone Employee 1',
            'phone' => '0501111111',
        ]);
        $employee2 = $this->createTestEmployee([
            'name' => 'Phone Employee 2',
            'phone' => '0502222222',
        ]);

        Livewire::test(ListEmployees::class)
            ->searchTable('0501111111')
            ->assertCanSeeTableRecords([$employee1])
            ->assertCanNotSeeTableRecords([$employee2]);
    }

    // ==========================================
    // Query Modifier Tests (Role-based visibility)
    // ==========================================

    #[Test]
    public function test_super_admin_can_see_all_employees(): void
    {
        $this->actingAs($this->superAdmin);

        // Create employees of different types
        $employee = $this->createTestEmployee(['name' => 'Regular Employee']);
        $adminEmployee = Employee::create([
            'name' => 'Admin Employee',
            'email' => 'adminemployee@test.com',
            'password' => bcrypt('password'),
            'phone' => '0503333333',
            'type' => UserType::ADMIN->value,
        ]);

        Livewire::test(ListEmployees::class)
            ->assertCanSeeTableRecords([$employee, $adminEmployee]);
    }

    #[Test]
    public function test_admin_cannot_see_super_admins(): void
    {
        $this->actingAs($this->admin);

        // Create a regular employee
        $employee = $this->createTestEmployee(['name' => 'Regular Employee']);

        // The super_admin from setUp should not be visible
        Livewire::test(ListEmployees::class)
            ->assertCanSeeTableRecords([$employee])
            ->assertCanNotSeeTableRecords([$this->superAdmin]);
    }

    #[Test]
    public function test_table_query_modifier_filters_based_on_user_type(): void
    {
        $this->actingAs($this->admin);

        // Create an employee that admin should see
        $regularEmployee = $this->createTestEmployee(['name' => 'Regular Employee For Admin']);

        // Admin should see regular employees
        Livewire::test(ListEmployees::class)
            ->assertCanSeeTableRecords([$regularEmployee]);
    }

    // ==========================================
    // Create Employee Tests
    // ==========================================

    #[Test]
    public function test_create_employee_page_accessible(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(EmployeeResource::getUrl('create'));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_can_create_employee_with_valid_data(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(CreateEmployee::class)
            ->fillForm([
                'name' => 'New Test Employee',
                'email' => 'newemployee@test.com',
                'password' => 'password123',
                'phone' => '0509876543',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Verify employee was created
        $this->assertDatabaseHas('users', [
            'name' => 'New Test Employee',
            'email' => 'newemployee@test.com',
            'type' => UserType::EMPLOYEE->value,
        ]);
    }

    #[Test]
    public function test_create_employee_validates_required_name(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(CreateEmployee::class)
            ->fillForm([
                'name' => '',
                'email' => 'test@test.com',
                'password' => 'password123',
                'phone' => '0509876543',
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    #[Test]
    public function test_create_employee_validates_required_phone(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(CreateEmployee::class)
            ->fillForm([
                'name' => 'Test Employee',
                'email' => 'test@test.com',
                'password' => 'password123',
                'phone' => '',
            ])
            ->call('create')
            ->assertHasFormErrors(['phone' => 'required']);
    }

    #[Test]
    public function test_create_employee_validates_required_email(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(CreateEmployee::class)
            ->fillForm([
                'name' => 'Test Employee',
                'email' => '',
                'password' => 'password123',
                'phone' => '0509876543',
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'required']);
    }

    #[Test]
    public function test_create_employee_validates_unique_email(): void
    {
        $this->actingAs($this->superAdmin);

        // Create an employee first
        $this->createTestEmployee(['email' => 'existing@test.com']);

        Livewire::test(CreateEmployee::class)
            ->fillForm([
                'name' => 'New Employee',
                'email' => 'existing@test.com',
                'password' => 'password123',
                'phone' => '0509876543',
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'unique']);
    }

    #[Test]
    public function test_create_employee_validates_required_password(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(CreateEmployee::class)
            ->fillForm([
                'name' => 'Test Employee',
                'email' => 'test@test.com',
                'password' => '',
                'phone' => '0509876543',
            ])
            ->call('create')
            ->assertHasFormErrors(['password' => 'required']);
    }

    #[Test]
    public function test_employee_cannot_access_create_page(): void
    {
        // Note: Current code only allows super_admin and admin to create employees
        $this->actingAs($this->employee);

        $response = $this->get(EmployeeResource::getUrl('create'));

        $response->assertStatus(403);
    }

    // ==========================================
    // Edit Employee Tests
    // ==========================================

    #[Test]
    public function test_edit_employee_page_accessible(): void
    {
        $this->actingAs($this->superAdmin);

        $employee = $this->createTestEmployee();

        $response = $this->get(EmployeeResource::getUrl('edit', ['record' => $employee]));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_can_edit_employee_data(): void
    {
        $this->actingAs($this->superAdmin);

        $employee = $this->createTestEmployee(['name' => 'Original Name']);

        Livewire::test(EditEmployee::class, ['record' => $employee->id])
            ->fillForm([
                'name' => 'Updated Name',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'name' => 'Updated Name',
        ]);
    }

    #[Test]
    public function test_edit_employee_form_prefilled(): void
    {
        $this->actingAs($this->superAdmin);

        $employee = $this->createTestEmployee([
            'name' => 'Prefilled Employee',
            'phone' => '0501234567',
            'secondary_phone' => '0557654321',
            'email' => 'prefilled@test.com',
        ]);

        Livewire::test(EditEmployee::class, ['record' => $employee->id])
            ->assertFormSet([
                'name' => 'Prefilled Employee',
                'phone' => '0501234567',
                'secondary_phone' => '0557654321',
                'email' => 'prefilled@test.com',
            ]);
    }

    #[Test]
    public function test_password_not_required_on_edit(): void
    {
        $this->actingAs($this->superAdmin);

        $employee = $this->createTestEmployee();

        Livewire::test(EditEmployee::class, ['record' => $employee->id])
            ->fillForm([
                'name' => 'Updated Without Password',
                'password' => '', // Empty password should be allowed on edit
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'name' => 'Updated Without Password',
        ]);
    }

    #[Test]
    public function test_employee_cannot_access_edit_page(): void
    {
        $this->actingAs($this->employee);

        $otherEmployee = $this->createTestEmployee();

        $response = $this->get(EmployeeResource::getUrl('edit', ['record' => $otherEmployee]));

        $response->assertStatus(403);
    }

    // ==========================================
    // Type Selection Tests
    // ==========================================

    #[Test]
    public function test_super_admin_can_set_employee_type(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(CreateEmployee::class)
            ->fillForm([
                'name' => 'Admin Type Employee',
                'email' => 'admintype@test.com',
                'password' => 'password123',
                'phone' => '0509876543',
                'type' => UserType::ADMIN->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'admintype@test.com',
            'type' => UserType::ADMIN->value,
        ]);
    }

    #[Test]
    public function test_default_type_is_employee_when_not_specified(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateEmployee::class)
            ->fillForm([
                'name' => 'Default Type Employee',
                'email' => 'defaulttype@test.com',
                'password' => 'password123',
                'phone' => '0509876543',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'defaulttype@test.com',
            'type' => UserType::EMPLOYEE->value,
        ]);
    }

    // ==========================================
    // Global Searchable Attributes Tests
    // ==========================================

    #[Test]
    public function test_globally_searchable_attributes_defined(): void
    {
        $attributes = EmployeeResource::getGloballySearchableAttributes();

        $this->assertContains('name', $attributes);
        $this->assertContains('email', $attributes);
        $this->assertContains('phone', $attributes);
        $this->assertContains('secondary_phone', $attributes);
    }

    // ==========================================
    // Pages Configuration Tests
    // ==========================================

    #[Test]
    public function test_resource_has_correct_pages(): void
    {
        $pages = EmployeeResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
    }

    // ==========================================
    // Model Configuration Tests
    // ==========================================

    #[Test]
    public function test_resource_uses_employee_model(): void
    {
        $this->assertEquals(Employee::class, EmployeeResource::getModel());
    }
}
