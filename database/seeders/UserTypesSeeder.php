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
                'employee_id' => 'EMP001',
                'department' => 'المبيعات',
                'position' => 'مدير المبيعات',
                'joining_date' => '2023-01-15',
                'salary' => 8000.00,
                'birth_date' => '1985-03-20',
                'address' => 'الرياض، النرجس',
                'emergency_contact' => 'فاطمة أحمد',
                'emergency_phone' => '0507654321',
            ],
            [
                'name' => 'سارة عبدالله المطيري',
                'email' => 'sara.almutairi.employee@aqarcrm.com',
                'phone' => '0551234567',
                'employee_id' => 'EMP002',
                'department' => 'الموارد البشرية',
                'position' => 'أخصائي موارد بشرية',
                'joining_date' => '2023-02-01',
                'salary' => 6500.00,
                'birth_date' => '1990-07-15',
                'address' => 'جدة، الحمراء',
                'emergency_contact' => 'محمد المطيري',
                'emergency_phone' => '0509876543',
            ],
            [
                'name' => 'خالد عبدالرحمن الشمري',
                'email' => 'khalid.alshammari.employee@aqarcrm.com',
                'phone' => '0541234567',
                'employee_id' => 'EMP003',
                'department' => 'الصيانة',
                'position' => 'فني صيانة أول',
                'joining_date' => '2022-11-10',
                'salary' => 5500.00,
                'birth_date' => '1988-12-05',
                'address' => 'الدمام، الروضة',
                'emergency_contact' => 'نورا الشمري',
                'emergency_phone' => '0502468135',
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
                'company_name' => 'شركة البراك للاستثمار العقاري',
                'business_type' => 'شركة',
                'commercial_register' => '1010123456',
                'tax_number' => '300123456789001',
                'bank_name' => 'البنك الأهلي السعودي',
                'bank_account_number' => '123456789',
                'iban' => 'SA1210000012345678901',
                'nationality' => 'سعودي',
                'birth_date' => '1970-05-10',
                'address' => 'الرياض، الملقا',
                'legal_representative' => 'عبدالعزيز البراك',
                'emergency_contact' => 'منى البراك',
                'emergency_phone' => '0557777777',
            ],
            [
                'name' => 'فاطمة أحمد القحطاني',
                'email' => 'fatima.alqahtani.owner@hotmail.com',
                'phone' => '0571234567',
                'company_name' => '',
                'business_type' => 'فردي',
                'nationality' => 'سعودي',
                'birth_date' => '1975-09-25',
                'address' => 'الخبر، الخالدية',
                'bank_name' => 'مصرف الراجحي',
                'bank_account_number' => '987654321',
                'iban' => 'SA8020000098765432101',
                'emergency_contact' => 'أحمد القحطاني',
                'emergency_phone' => '0588888888',
            ],
            [
                'name' => 'محمد سعد الغامدي',
                'email' => 'mohammed.alghamdi.owner@yahoo.com',
                'phone' => '0581234567',
                'company_name' => 'مؤسسة الغامدي التجارية',
                'business_type' => 'مؤسسة',
                'commercial_register' => '4030123456',
                'tax_number' => '300987654321001',
                'nationality' => 'سعودي',
                'birth_date' => '1965-11-30',
                'address' => 'مكة المكرمة، العزيزية',
                'bank_name' => 'بنك الرياض',
                'bank_account_number' => '555666777',
                'iban' => 'SA4420000055566677701',
                'emergency_contact' => 'عائشة الغامدي',
                'emergency_phone' => '0599999999',
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
                'nationality' => 'سعودي',
                'birth_date' => '1992-04-18',
                'marital_status' => 'متزوج',
                'family_size' => 4,
                'occupation' => 'مهندس',
                'employer_name' => 'شركة أرامكو السعودية',
                'employer_phone' => '0138901234',
                'monthly_income' => 12000.00,
                'monthly_rent' => 2500.00,
                'security_deposit' => 5000.00,
                'contract_start_date' => '2024-01-01',
                'contract_end_date' => '2024-12-31',
                'guarantor_name' => 'علي حسن الحربي',
                'guarantor_phone' => '0506666666',
                'guarantor_address' => 'الرياض، الملز',
                'guarantor_id_number' => '1234567890',
                'emergency_contact' => 'مريم الحربي',
                'emergency_phone' => '0505555555',
                'previous_address' => 'الدمام، الفيصلية',
                'has_pets' => false,
            ],
            [
                'name' => 'نورا محمد الأنصاري',
                'email' => 'nora.alansari.tenant@outlook.com',
                'phone' => '0521234567',
                'nationality' => 'سعودي',
                'birth_date' => '1988-08-12',
                'marital_status' => 'أعزب',
                'family_size' => 1,
                'occupation' => 'طبيبة',
                'employer_name' => 'مستشفى الملك فيصل التخصصي',
                'employer_phone' => '0114442222',
                'monthly_income' => 15000.00,
                'monthly_rent' => 3000.00,
                'security_deposit' => 6000.00,
                'contract_start_date' => '2024-03-01',
                'contract_end_date' => '2025-02-28',
                'guarantor_name' => 'محمد حامد الأنصاري',
                'guarantor_phone' => '0507777777',
                'guarantor_address' => 'الرياض، العليا',
                'guarantor_id_number' => '9876543210',
                'emergency_contact' => 'ليلى الأنصاري',
                'emergency_phone' => '0508888888',
                'previous_address' => 'جدة، الصفا',
                'has_pets' => true,
                'pet_details' => 'قطة صغيرة واحدة',
            ],
            [
                'name' => 'أحمد عبدالله الزهراني',
                'email' => 'ahmed.alzahrani.tenant@icloud.com',
                'phone' => '0531234567',
                'nationality' => 'سعودي',
                'birth_date' => '1995-01-22',
                'marital_status' => 'متزوج',
                'family_size' => 3,
                'occupation' => 'محاسب',
                'employer_name' => 'شركة سابك',
                'employer_phone' => '0138887777',
                'monthly_income' => 9000.00,
                'monthly_rent' => 2000.00,
                'security_deposit' => 4000.00,
                'contract_start_date' => '2024-05-15',
                'contract_end_date' => '2025-05-14',
                'guarantor_name' => 'عبدالله سعد الزهراني',
                'guarantor_phone' => '0509999999',
                'guarantor_address' => 'الطائف، الحوية',
                'guarantor_id_number' => '5678901234',
                'emergency_contact' => 'هند الزهراني',
                'emergency_phone' => '0501111111',
                'previous_address' => 'الرياض، الخزامى',
                'has_pets' => false,
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
