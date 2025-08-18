<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PropertyContract;
use App\Models\UnitContract;
use App\Models\User;
use App\Models\Property;
use App\Models\Unit;
use Carbon\Carbon;

class ContractsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get sample users
        $owners = User::role('owner')->take(5)->get();
        $tenants = User::role('tenant')->take(10)->get();
        $properties = Property::take(5)->get();
        $units = Unit::take(10)->get();

        if ($owners->isEmpty() || $tenants->isEmpty() || $properties->isEmpty() || $units->isEmpty()) {
            $this->command->warn('Skipping contract seeding - required data not found (owners, tenants, properties, or units)');
            return;
        }

        // Create Property Contracts
        $this->createPropertyContracts($owners, $properties);

        // Create Unit Contracts
        $this->createUnitContracts($tenants, $units, $properties);
    }

    /**
     * Create sample property contracts.
     */
    private function createPropertyContracts($owners, $properties): void
    {
        $statuses = ['draft', 'active', 'suspended', 'expired', 'terminated'];
        
        foreach ($properties as $index => $property) {
            if ($index < $owners->count()) {
                $owner = $owners->get($index);
                $status = $statuses[array_rand($statuses)];
                
                $startDate = Carbon::now()->subMonths(rand(1, 24));
                $durationMonths = [12, 18, 24, 36][array_rand([12, 18, 24, 36])];
                $endDate = $startDate->copy()->addMonths($durationMonths)->subDay();

                PropertyContract::create([
                    'owner_id' => $owner->id,
                    'property_id' => $property->id,
                    'commission_rate' => [3.0, 5.0, 7.5, 10.0][array_rand([3.0, 5.0, 7.5, 10.0])],
                    'duration_months' => $durationMonths,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'contract_status' => $status,
                    'notary_number' => 'NOT-' . rand(100000, 999999),
                    'payment_day' => rand(1, 28),
                    'auto_renew' => (bool) rand(0, 1),
                    'notice_period_days' => [30, 60, 90][array_rand([30, 60, 90])],
                    'terms_and_conditions' => 'شروط وأحكام عقد إدارة العقار. يتضمن العقد جميع الالتزامات والحقوق لكلا الطرفين.',
                    'notes' => 'ملاحظات خاصة بالعقد.',
                    'created_by' => 1, // Admin user
                    'approved_by' => $status === 'active' ? 1 : null,
                    'approved_at' => $status === 'active' ? $startDate : null,
                ]);
            }
        }

        $this->command->info('Created ' . min($owners->count(), $properties->count()) . ' property contracts');
    }

    /**
     * Create sample unit contracts.
     */
    private function createUnitContracts($tenants, $units, $properties): void
    {
        $statuses = ['draft', 'active', 'expired', 'terminated', 'renewed'];
        $paymentFrequencies = ['monthly', 'quarterly', 'semi_annually', 'annually'];
        $paymentMethods = ['bank_transfer', 'cash', 'check', 'online'];
        
        foreach ($units as $index => $unit) {
            if ($index < $tenants->count()) {
                $tenant = $tenants->get($index);
                $status = $statuses[array_rand($statuses)];
                $property = $unit->property ?? $properties->random();
                
                $startDate = Carbon::now()->subMonths(rand(1, 18));
                $durationMonths = [6, 12, 18, 24][array_rand([6, 12, 18, 24])];
                $endDate = $startDate->copy()->addMonths($durationMonths)->subDay();
                
                $monthlyRent = rand(2000, 8000);
                $securityDeposit = $monthlyRent; // Usually one month

                UnitContract::create([
                    'tenant_id' => $tenant->id,
                    'unit_id' => $unit->id,
                    'property_id' => $property->id,
                    'monthly_rent' => $monthlyRent,
                    'security_deposit' => $securityDeposit,
                    'duration_months' => $durationMonths,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'contract_status' => $status,
                    'payment_frequency' => $paymentFrequencies[array_rand($paymentFrequencies)],
                    'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                    'grace_period_days' => [3, 5, 7, 10][array_rand([3, 5, 7, 10])],
                    'late_fee_rate' => [0, 1.0, 2.5, 5.0][array_rand([0, 1.0, 2.5, 5.0])],
                    'utilities_included' => (bool) rand(0, 1),
                    'furnished' => (bool) rand(0, 1),
                    'evacuation_notice_days' => [30, 60, 90][array_rand([30, 60, 90])],
                    'terms_and_conditions' => 'شروط وأحكام عقد الإيجار. يتضمن جميع الحقوق والواجبات للمستأجر والمؤجر.',
                    'special_conditions' => rand(0, 1) ? 'شروط خاصة إضافية حسب اتفاق الطرفين.' : null,
                    'notes' => 'ملاحظات خاصة بعقد الإيجار.',
                    'created_by' => 1, // Admin user
                    'approved_by' => $status === 'active' ? 1 : null,
                    'approved_at' => $status === 'active' ? $startDate : null,
                ]);

                // Update unit status if contract is active
                if ($status === 'active') {
                    // Find occupied status ID (assuming 2 is occupied)
                    $occupiedStatusId = \App\Models\UnitStatus::where('slug', 'occupied')->first()?->id ?? 2;
                    $unit->update(['status_id' => $occupiedStatusId]);
                    $unit->update(['current_tenant_id' => $tenant->id]);
                }
            }
        }

        $this->command->info('Created ' . min($tenants->count(), $units->count()) . ' unit contracts');
    }
}