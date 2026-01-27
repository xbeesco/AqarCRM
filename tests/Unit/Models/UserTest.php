<?php

namespace Tests\Unit\Models;

use App\Enums\UserType;
use App\Models\CollectionPayment;
use App\Models\Location;
use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\UnitContract;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

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
                'name_ar' => 'شقة',
                'name_en' => 'Apartment',
                'slug' => 'apartment',
            ]
        );

        // Create property status
        PropertyStatus::firstOrCreate(
            ['id' => 1],
            [
                'name_ar' => 'متاح',
                'name_en' => 'Available',
                'slug' => 'available',
            ]
        );

        // Create location
        Location::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Test Location',
                'name_ar' => 'موقع اختبار',
                'name_en' => 'Test Location',
                'level' => 1,
            ]
        );

        // Create unit type
        UnitType::firstOrCreate(
            ['id' => 1],
            [
                'name_ar' => 'شقة',
                'name_en' => 'Apartment',
                'slug' => 'apartment',
            ]
        );
    }

    protected function createUser(string $type, array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'email' => 'testuser'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'phone' => '050'.rand(1000000, 9999999),
            'type' => $type,
        ], $attributes));
    }

    // ==============================================
    // User Creation Tests
    // ==============================================

    #[Test]
    public function user_can_be_created_with_all_fillable_attributes(): void
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'phone' => '0501234567',
            'secondary_phone' => '0509876543',
            'identity_file' => 'path/to/identity.pdf',
            'type' => UserType::ADMIN->value,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '0501234567',
            'secondary_phone' => '0509876543',
            'type' => 'admin',
        ]);
    }

    #[Test]
    public function user_password_is_hidden(): void
    {
        $user = $this->createUser(UserType::ADMIN->value);

        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }

    #[Test]
    public function user_password_is_hashed(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'plainpassword',
            'type' => UserType::ADMIN->value,
        ]);

        // The password should be hashed, not plain text
        $this->assertNotEquals('plainpassword', $user->password);
        $this->assertTrue(password_verify('plainpassword', $user->password));
    }

    #[Test]
    public function user_email_verified_at_is_cast_to_datetime(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'verified@example.com',
            'password' => bcrypt('password'),
            'type' => UserType::ADMIN->value,
            'email_verified_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->email_verified_at);
    }

    // ==============================================
    // Phone Attribute Mutator Tests
    // ==============================================

    #[Test]
    public function phone_attribute_removes_non_numeric_characters(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'phone@example.com',
            'password' => bcrypt('password'),
            'phone' => '+966-50-123-4567',
            'type' => UserType::ADMIN->value,
        ]);

        $this->assertEquals('966501234567', $user->phone);
    }

    #[Test]
    public function secondary_phone_attribute_removes_non_numeric_characters(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'secondary@example.com',
            'password' => bcrypt('password'),
            'phone' => '0501234567',
            'secondary_phone' => '+966 50 987 6543',
            'type' => UserType::ADMIN->value,
        ]);

        $this->assertEquals('966509876543', $user->secondary_phone);
    }

    // ==============================================
    // Email Generation Tests
    // ==============================================

    #[Test]
    public function generate_email_creates_correct_format(): void
    {
        $email = User::generateEmail('0501234567');

        $this->assertStringContainsString('0501234567@', $email);
    }

    // ==============================================
    // User Type Scope Tests
    // ==============================================

    #[Test]
    public function scope_by_type_filters_by_single_type_string(): void
    {
        $admin = $this->createUser(UserType::ADMIN->value);
        $employee = $this->createUser(UserType::EMPLOYEE->value);

        $admins = User::byType('admin')->get();

        $this->assertCount(1, $admins);
        $this->assertEquals($admin->id, $admins->first()->id);
    }

    #[Test]
    public function scope_by_type_filters_by_user_type_enum(): void
    {
        $tenant = $this->createUser(UserType::TENANT->value);
        $owner = $this->createUser(UserType::OWNER->value);

        $tenants = User::byType(UserType::TENANT)->get();

        $this->assertCount(1, $tenants);
        $this->assertEquals($tenant->id, $tenants->first()->id);
    }

    #[Test]
    public function scope_by_type_filters_by_array_of_types(): void
    {
        $admin = $this->createUser(UserType::ADMIN->value);
        $employee = $this->createUser(UserType::EMPLOYEE->value);
        $owner = $this->createUser(UserType::OWNER->value);

        $staff = User::byType(['admin', 'employee'])->get();

        $this->assertCount(2, $staff);
        $staffIds = $staff->pluck('id')->toArray();
        $this->assertContains($admin->id, $staffIds);
        $this->assertContains($employee->id, $staffIds);
    }

    #[Test]
    public function scope_by_type_supports_staff_keyword(): void
    {
        $superAdmin = $this->createUser(UserType::SUPER_ADMIN->value);
        $admin = $this->createUser(UserType::ADMIN->value);
        $employee = $this->createUser(UserType::EMPLOYEE->value);
        $owner = $this->createUser(UserType::OWNER->value);

        $staff = User::byType('staff')->get();

        $this->assertCount(3, $staff);
        $staffIds = $staff->pluck('id')->toArray();
        $this->assertContains($superAdmin->id, $staffIds);
        $this->assertContains($admin->id, $staffIds);
        $this->assertContains($employee->id, $staffIds);
    }

    #[Test]
    public function scope_by_type_supports_clients_keyword(): void
    {
        $owner = $this->createUser(UserType::OWNER->value);
        $tenant = $this->createUser(UserType::TENANT->value);
        $admin = $this->createUser(UserType::ADMIN->value);

        $clients = User::byType('clients')->get();

        $this->assertCount(2, $clients);
        $clientIds = $clients->pluck('id')->toArray();
        $this->assertContains($owner->id, $clientIds);
        $this->assertContains($tenant->id, $clientIds);
    }

    #[Test]
    public function scope_by_type_supports_admin_keyword(): void
    {
        $superAdmin = $this->createUser(UserType::SUPER_ADMIN->value);
        $admin = $this->createUser(UserType::ADMIN->value);
        $employee = $this->createUser(UserType::EMPLOYEE->value);

        $admins = User::byType('admin')->get();

        $this->assertCount(2, $admins);
        $adminIds = $admins->pluck('id')->toArray();
        $this->assertContains($superAdmin->id, $adminIds);
        $this->assertContains($admin->id, $adminIds);
    }

    #[Test]
    public function legacy_scope_employees_uses_by_type(): void
    {
        $superAdmin = $this->createUser(UserType::SUPER_ADMIN->value);
        $admin = $this->createUser(UserType::ADMIN->value);
        $employee = $this->createUser(UserType::EMPLOYEE->value);

        $employees = User::employees()->get();

        $this->assertCount(3, $employees);
    }

    #[Test]
    public function legacy_scope_admins_uses_by_type(): void
    {
        $superAdmin = $this->createUser(UserType::SUPER_ADMIN->value);
        $admin = $this->createUser(UserType::ADMIN->value);
        $employee = $this->createUser(UserType::EMPLOYEE->value);

        $admins = User::admins()->get();

        $this->assertCount(2, $admins);
    }

    #[Test]
    public function legacy_scope_owners_uses_by_type(): void
    {
        $owner = $this->createUser(UserType::OWNER->value);
        $tenant = $this->createUser(UserType::TENANT->value);

        $owners = User::owners()->get();

        $this->assertCount(1, $owners);
        $this->assertEquals($owner->id, $owners->first()->id);
    }

    #[Test]
    public function legacy_scope_tenants_uses_by_type(): void
    {
        $owner = $this->createUser(UserType::OWNER->value);
        $tenant = $this->createUser(UserType::TENANT->value);

        $tenants = User::tenants()->get();

        $this->assertCount(1, $tenants);
        $this->assertEquals($tenant->id, $tenants->first()->id);
    }

    #[Test]
    public function legacy_scope_of_type_uses_by_type(): void
    {
        $owner = $this->createUser(UserType::OWNER->value);
        $tenant = $this->createUser(UserType::TENANT->value);

        $owners = User::ofType('owner')->get();

        $this->assertCount(1, $owners);
        $this->assertEquals($owner->id, $owners->first()->id);
    }

    // ==============================================
    // User Type Helper Methods Tests
    // ==============================================

    #[Test]
    public function get_user_type_returns_user_type_enum(): void
    {
        $user = $this->createUser(UserType::ADMIN->value);

        $userType = $user->getUserType();

        $this->assertInstanceOf(UserType::class, $userType);
        $this->assertEquals(UserType::ADMIN, $userType);
    }

    #[Test]
    public function get_user_type_returns_null_for_invalid_type(): void
    {
        $user = $this->createUser('invalid_type');

        $userType = $user->getUserType();

        $this->assertNull($userType);
    }

    #[Test]
    public function get_type_label_returns_label_string(): void
    {
        $user = $this->createUser(UserType::ADMIN->value);

        $label = $user->getTypeLabel();

        $this->assertIsString($label);
        $this->assertNotEmpty($label);
    }

    #[Test]
    public function get_type_label_returns_unknown_for_invalid_type(): void
    {
        $user = $this->createUser('invalid_type');

        $label = $user->getTypeLabel();

        $this->assertEquals('Unknown', $label);
    }

    #[Test]
    public function get_type_color_returns_color_string(): void
    {
        $user = $this->createUser(UserType::ADMIN->value);

        $color = $user->getTypeColor();

        $this->assertIsString($color);
        $this->assertNotEmpty($color);
    }

    #[Test]
    public function get_type_color_returns_gray_for_invalid_type(): void
    {
        $user = $this->createUser('invalid_type');

        $color = $user->getTypeColor();

        $this->assertEquals('gray', $color);
    }

    // ==============================================
    // User Type Check Methods Tests
    // ==============================================

    #[Test]
    public function is_super_admin_returns_true_for_super_admin(): void
    {
        $user = $this->createUser(UserType::SUPER_ADMIN->value);

        $this->assertTrue($user->isSuperAdmin());
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isEmployee());
        $this->assertFalse($user->isOwner());
        $this->assertFalse($user->isTenant());
    }

    #[Test]
    public function is_admin_returns_true_for_admin(): void
    {
        $user = $this->createUser(UserType::ADMIN->value);

        $this->assertFalse($user->isSuperAdmin());
        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isEmployee());
        $this->assertFalse($user->isOwner());
        $this->assertFalse($user->isTenant());
    }

    #[Test]
    public function is_employee_returns_true_for_employee(): void
    {
        $user = $this->createUser(UserType::EMPLOYEE->value);

        $this->assertFalse($user->isSuperAdmin());
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->isEmployee());
        $this->assertFalse($user->isOwner());
        $this->assertFalse($user->isTenant());
    }

    #[Test]
    public function is_owner_returns_true_for_owner(): void
    {
        $user = $this->createUser(UserType::OWNER->value);

        $this->assertFalse($user->isSuperAdmin());
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isEmployee());
        $this->assertTrue($user->isOwner());
        $this->assertFalse($user->isTenant());
    }

    #[Test]
    public function is_tenant_returns_true_for_tenant(): void
    {
        $user = $this->createUser(UserType::TENANT->value);

        $this->assertFalse($user->isSuperAdmin());
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isEmployee());
        $this->assertFalse($user->isOwner());
        $this->assertTrue($user->isTenant());
    }

    // ==============================================
    // Relationship Tests
    // ==============================================

    #[Test]
    public function properties_relationship_returns_properties_for_owner(): void
    {
        $owner = $this->createUser(UserType::OWNER->value);

        $property1 = Property::create([
            'name' => 'Property 1',
            'owner_id' => $owner->id,
            'type_id' => 1,
            'status_id' => 1,
            'location_id' => 1,
            'address' => 'Address 1',
        ]);

        $property2 = Property::create([
            'name' => 'Property 2',
            'owner_id' => $owner->id,
            'type_id' => 1,
            'status_id' => 1,
            'location_id' => 1,
            'address' => 'Address 2',
        ]);

        $owner = $owner->fresh();

        $this->assertCount(2, $owner->properties);
        $this->assertInstanceOf(Property::class, $owner->properties->first());
        $propertyIds = $owner->properties->pluck('id')->toArray();
        $this->assertContains($property1->id, $propertyIds);
        $this->assertContains($property2->id, $propertyIds);
    }

    #[Test]
    public function collection_payments_relationship_returns_payments_for_tenant(): void
    {
        $owner = $this->createUser(UserType::OWNER->value);
        $tenant = $this->createUser(UserType::TENANT->value);

        $property = Property::create([
            'name' => 'Test Property',
            'owner_id' => $owner->id,
            'type_id' => 1,
            'status_id' => 1,
            'location_id' => 1,
            'address' => 'Test Address',
        ]);

        $unit = Unit::create([
            'name' => 'Unit 101',
            'property_id' => $property->id,
            'unit_type_id' => 1,
            'rent_price' => 2000,
            'floor_number' => 1,
        ]);

        $startDate = now();
        $durationMonths = 12;
        $contract = UnitContract::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'monthly_rent' => 2000,
            'duration_months' => $durationMonths,
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addMonths($durationMonths)->subDay(),
            'contract_status' => 'draft', // Use draft to prevent auto payment generation
        ]);

        // Create collection payments manually
        CollectionPayment::create([
            'payment_number' => 'PAY-USR-001',
            'unit_contract_id' => $contract->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'amount' => 2000,
            'due_date_start' => now()->subDays(30),
            'due_date_end' => now()->subDays(25),
        ]);

        CollectionPayment::create([
            'payment_number' => 'PAY-USR-002',
            'unit_contract_id' => $contract->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'amount' => 2000,
            'due_date_start' => now(),
            'due_date_end' => now()->addDays(5),
        ]);

        $tenant = $tenant->fresh();

        $this->assertCount(2, $tenant->collectionPayments);
        $this->assertInstanceOf(CollectionPayment::class, $tenant->collectionPayments->first());
    }

    #[Test]
    public function unit_contracts_relationship_returns_contracts_for_tenant(): void
    {
        $owner = $this->createUser(UserType::OWNER->value);
        $tenant = $this->createUser(UserType::TENANT->value);

        $property = Property::create([
            'name' => 'Test Property',
            'owner_id' => $owner->id,
            'type_id' => 1,
            'status_id' => 1,
            'location_id' => 1,
            'address' => 'Test Address',
        ]);

        $unit = Unit::create([
            'name' => 'Unit 101',
            'property_id' => $property->id,
            'unit_type_id' => 1,
            'rent_price' => 2000,
            'floor_number' => 1,
        ]);

        $startDate = now();
        $contract = UnitContract::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'monthly_rent' => 2000,
            'duration_months' => 12,
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addMonths(12)->subDay(),
            'contract_status' => 'draft',
        ]);

        $tenant = $tenant->fresh();

        $this->assertCount(1, $tenant->unitContracts);
        $this->assertInstanceOf(UnitContract::class, $tenant->unitContracts->first());
        $this->assertEquals($contract->id, $tenant->unitContracts->first()->id);
    }

    // ==============================================
    // Filament Access Control Tests
    // ==============================================

    #[Test]
    public function super_admin_can_access_panel(): void
    {
        $user = $this->createUser(UserType::SUPER_ADMIN->value);

        // Create a mock Panel object
        $panel = $this->createMock(\Filament\Panel::class);

        $this->assertTrue($user->canAccessPanel($panel));
    }

    #[Test]
    public function admin_can_access_panel(): void
    {
        $user = $this->createUser(UserType::ADMIN->value);

        $panel = $this->createMock(\Filament\Panel::class);

        $this->assertTrue($user->canAccessPanel($panel));
    }

    #[Test]
    public function employee_can_access_panel(): void
    {
        $user = $this->createUser(UserType::EMPLOYEE->value);

        $panel = $this->createMock(\Filament\Panel::class);

        $this->assertTrue($user->canAccessPanel($panel));
    }

    #[Test]
    public function owner_cannot_access_panel(): void
    {
        $user = $this->createUser(UserType::OWNER->value);

        $panel = $this->createMock(\Filament\Panel::class);

        $this->assertFalse($user->canAccessPanel($panel));
    }

    #[Test]
    public function tenant_cannot_access_panel(): void
    {
        $user = $this->createUser(UserType::TENANT->value);

        $panel = $this->createMock(\Filament\Panel::class);

        $this->assertFalse($user->canAccessPanel($panel));
    }

    #[Test]
    public function user_with_null_type_cannot_access_panel(): void
    {
        $user = User::create([
            'name' => 'No Type User',
            'email' => 'notype@example.com',
            'password' => bcrypt('password'),
        ]);

        $panel = $this->createMock(\Filament\Panel::class);

        $this->assertFalse($user->canAccessPanel($panel));
    }

    // ==============================================
    // Soft Deletes Tests
    // ==============================================

    #[Test]
    public function user_can_be_soft_deleted(): void
    {
        $user = $this->createUser(UserType::ADMIN->value);

        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    #[Test]
    public function soft_deleted_user_can_be_restored(): void
    {
        $user = $this->createUser(UserType::ADMIN->value);
        $user->delete();

        $user->restore();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function soft_deleted_user_not_included_in_queries(): void
    {
        $user1 = $this->createUser(UserType::ADMIN->value, ['email' => 'user1@test.com']);
        $user2 = $this->createUser(UserType::ADMIN->value, ['email' => 'user2@test.com']);

        $user1->delete();

        $admins = User::byType('admin')->get();

        $this->assertCount(1, $admins);
        $this->assertEquals($user2->id, $admins->first()->id);
    }

    #[Test]
    public function soft_deleted_user_included_with_trashed(): void
    {
        $user1 = $this->createUser(UserType::ADMIN->value, ['email' => 'user1@test.com']);
        $user2 = $this->createUser(UserType::ADMIN->value, ['email' => 'user2@test.com']);

        $user1->delete();

        $admins = User::withTrashed()->byType('admin')->get();

        $this->assertCount(2, $admins);
    }

    // ==============================================
    // Auto Email/Password Generation for Owner/Tenant
    // ==============================================

    #[Test]
    public function owner_email_auto_generated_from_phone_when_not_provided(): void
    {
        $owner = Owner::create([
            'name' => 'Auto Email Owner',
            'phone' => '0501234567',
            'password' => bcrypt('password'),
        ]);

        $this->assertNotNull($owner->email);
        $this->assertStringContainsString('0501234567', $owner->email);
    }

    #[Test]
    public function tenant_email_auto_generated_from_phone_when_not_provided(): void
    {
        $tenant = Tenant::create([
            'name' => 'Auto Email Tenant',
            'phone' => '0509876543',
            'password' => bcrypt('password'),
        ]);

        $this->assertNotNull($tenant->email);
        $this->assertStringContainsString('0509876543', $tenant->email);
    }

    #[Test]
    public function owner_password_auto_generated_when_not_provided(): void
    {
        $owner = Owner::create([
            'name' => 'Auto Password Owner',
            'phone' => '0501234567',
            'email' => 'autopass@test.com',
        ]);

        $this->assertNotNull($owner->password);
        $this->assertNotEmpty($owner->password);
    }

    #[Test]
    public function tenant_password_auto_generated_when_not_provided(): void
    {
        $tenant = Tenant::create([
            'name' => 'Auto Password Tenant',
            'phone' => '0509876543',
            'email' => 'autopasstenant@test.com',
        ]);

        $this->assertNotNull($tenant->password);
        $this->assertNotEmpty($tenant->password);
    }
}
