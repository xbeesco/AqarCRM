<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Owner;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Property;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure roles exist
        Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'tenant', 'guard_name' => 'web']);

        $this->seedEmployees();
        $this->seedOwners();
        $this->seedTenants();
    }

    private function seedEmployees()
    {
        $employees = [
            [
                'name' => 'أحمد محمد العلي',
                'email' => 'ahmed.ali.employee@aqarcrm.com',
                'phone' => '0501234567',
                'type' => 'employee',
            ],
            [
                'name' => 'سارة عبدالله المطيري',
                'email' => 'sara.almutairi.employee@aqarcrm.com',
                'phone' => '0551234567',
                'type' => 'employee',
            ],
            [
                'name' => 'خالد عبدالرحمن الشمري',
                'email' => 'khalid.alshammari.employee@aqarcrm.com',
                'phone' => '0541234567',
                'type' => 'employee',
            ],
        ];

        foreach ($employees as $employeeData) {
            $employee = User::updateOrCreate(
                ['email' => $employeeData['email']],
                [
                    ...$employeeData,
                    'password' => Hash::make('password123'),
                ]
            );
            
            // Assign employee role
            $employeeRole = Role::where('name', 'employee')->first();
            if ($employeeRole) {
                $employee->assignRole($employeeRole);
            }
            
            echo "Created employee: {$employee->name}\n";
        }
    }

    private function seedOwners()
    {
        $owners = [
            [
                'name' => 'عبدالعزيز محمد البراك',
                'email' => 'abdulaziz.albarrak.owner@gmail.com',
                'phone' => '0561234567',
                'secondary_phone' => '0501234567',
                'type' => 'owner',
            ],
            [
                'name' => 'فاطمة أحمد القحطاني',
                'email' => 'fatima.alqahtani.owner@hotmail.com',
                'phone' => '0571234567',
                'type' => 'owner',
            ],
            [
                'name' => 'محمد سعد الغامدي',
                'email' => 'mohammed.alghamdi.owner@yahoo.com',
                'phone' => '0581234567',
                'type' => 'owner',
            ],
        ];

        foreach ($owners as $ownerData) {
            $owner = User::updateOrCreate(
                ['email' => $ownerData['email']],
                [
                    ...$ownerData,
                    'password' => Hash::make('password123'),
                ]
            );
            
            // Assign owner role
            $ownerRole = Role::where('name', 'owner')->first();
            if ($ownerRole) {
                $owner->assignRole($ownerRole);
            }
            
            echo "Created owner: {$owner->name}\n";
        }
    }

    private function seedTenants()
    {
        $tenants = [
            [
                'name' => 'يوسف علي الحربي',
                'email' => 'youssef.alharbi.tenant@gmail.com',
                'phone' => '0591234567',
                'type' => 'tenant',
            ],
            [
                'name' => 'نورا محمد الأنصاري',
                'email' => 'nora.alansari.tenant@outlook.com',
                'phone' => '0521234567',
                'type' => 'tenant',
            ],
            [
                'name' => 'أحمد عبدالله الزهراني',
                'email' => 'ahmed.alzahrani.tenant@icloud.com',
                'phone' => '0531234567',
                'type' => 'tenant',
            ],
        ];

        foreach ($tenants as $tenantData) {
            $tenant = User::updateOrCreate(
                ['email' => $tenantData['email']],
                [
                    ...$tenantData,
                    'password' => Hash::make('password123'),
                ]
            );
            
            // Assign tenant role
            $tenantRole = Role::where('name', 'tenant')->first();
            if ($tenantRole) {
                $tenant->assignRole($tenantRole);
            }
            
            echo "Created tenant: {$tenant->name}\n";
        }
    }
}
