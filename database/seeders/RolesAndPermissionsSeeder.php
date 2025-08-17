<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $superAdmin = Role::create(['name' => 'super_admin']);
        $admin = Role::create(['name' => 'admin']);
        $employee = Role::create(['name' => 'employee']);
        $owner = Role::create(['name' => 'owner']);
        $tenant = Role::create(['name' => 'tenant']);

        // Create super admin user
        $superAdminUser = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@aqarcrm.com',
            'password' => Hash::make('password'),
            'phone' => '0500000000'
        ]);
        $superAdminUser->assignRole('super_admin');

        // Create sample admin user
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'phone' => '0500000001'
        ]);
        $adminUser->assignRole('admin');

        // Create sample employee
        $employeeUser = User::create([
            'name' => 'Employee User',
            'email' => 'employee@example.com',
            'password' => Hash::make('password'),
            'phone' => '0500000002'
        ]);
        $employeeUser->assignRole('employee');

        // Create sample owner
        $ownerUser = User::create([
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'phone' => '0500000003'
        ]);
        $ownerUser->assignRole('owner');

        // Create sample tenant
        $tenantUser = User::create([
            'name' => 'Tenant User',
            'email' => 'tenant@example.com',
            'password' => Hash::make('password'),
            'phone' => '0500000004'
        ]);
        $tenantUser->assignRole('tenant');
    }
}