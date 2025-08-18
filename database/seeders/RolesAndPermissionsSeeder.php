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

        // Create roles (check if they exist first)
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $employee = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        $owner = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $tenant = Role::firstOrCreate(['name' => 'tenant', 'guard_name' => 'web']);

        // Create super admin user (check if exists first)
        $superAdminUser = User::firstOrCreate(
            ['email' => 'admin@aqarcrm.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'phone' => '0500000000'
            ]
        );
        if (!$superAdminUser->hasRole('super_admin')) {
            $superAdminUser->assignRole('super_admin');
        }

        // Create sample admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'phone' => '0500000001'
            ]
        );
        if (!$adminUser->hasRole('admin')) {
            $adminUser->assignRole('admin');
        }

        // Create sample employee
        $employeeUser = User::firstOrCreate(
            ['email' => 'employee@example.com'],
            [
                'name' => 'Employee User',
                'password' => Hash::make('password'),
                'phone' => '0500000002'
            ]
        );
        if (!$employeeUser->hasRole('employee')) {
            $employeeUser->assignRole('employee');
        }

        // Create sample owner
        $ownerUser = User::firstOrCreate(
            ['email' => 'owner@example.com'],
            [
                'name' => 'Owner User',
                'password' => Hash::make('password'),
                'phone' => '0500000003'
            ]
        );
        if (!$ownerUser->hasRole('owner')) {
            $ownerUser->assignRole('owner');
        }

        // Create sample tenant
        $tenantUser = User::firstOrCreate(
            ['email' => 'tenant@example.com'],
            [
                'name' => 'Tenant User',
                'password' => Hash::make('password'),
                'phone' => '0500000004'
            ]
        );
        if (!$tenantUser->hasRole('tenant')) {
            $tenantUser->assignRole('tenant');
        }
    }
}