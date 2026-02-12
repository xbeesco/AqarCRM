<?php

namespace App\Console\Commands;

use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedContracts extends Command
{
    protected $signature = 'app:seed-contracts {--contracts-per-unit=100 : Number of contracts per unit}';

    protected $description = 'Seed unit contracts for performance testing';

    public function handle(): int
    {
        $contractsPerUnit = (int) $this->option('contracts-per-unit');

        $this->info('Starting contracts seeding...');

        // Get tenant
        $tenant = User::where('email', 'test-tenant@test.com')->first();
        if (! $tenant) {
            $tenant = User::where('type', 'tenant')->first();
        }
        if (! $tenant) {
            $this->error('No tenant found!');

            return 1;
        }

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Get all units
        $units = Unit::select('id', 'property_id')->get();
        $totalUnits = $units->count();

        $this->info("Found {$totalUnits} units. Creating {$contractsPerUnit} contracts per unit...");

        $now = now();
        $frequencies = ['monthly', 'quarterly', 'semi_annually', 'annually'];
        $methods = ['bank_transfer', 'cash', 'check', 'online'];
        $durations = [6, 12, 18, 24];
        $gracePeriods = [3, 5, 7, 10];
        $evacuationDays = [30, 60, 90];

        $contractNumber = DB::table('unit_contracts')->max('id') ?? 0;
        $totalContracts = 0;
        $chunkSize = 1000;
        $contracts = [];

        $progressBar = $this->output->createProgressBar($totalUnits);
        $progressBar->start();

        foreach ($units as $unit) {
            for ($k = 1; $k <= $contractsPerUnit; $k++) {
                $contractNumber++;

                // Create varied date ranges
                $yearsBack = ($k - 1) * 0.12;
                $startDate = Carbon::now()->subYears($yearsBack)->subMonths(rand(0, 11));
                $durationMonths = $durations[array_rand($durations)];
                $endDate = (clone $startDate)->addMonths($durationMonths)->subDay();

                // Determine status based on dates
                $status = 'expired';
                if ($endDate->isFuture() && $startDate->isPast()) {
                    $status = 'active';
                } elseif ($startDate->isFuture()) {
                    $status = 'draft';
                }

                // Make first 2 contracts specifically active
                if ($k <= 2) {
                    $startDate = Carbon::now()->subMonths(rand(1, 6));
                    $endDate = Carbon::now()->addMonths(rand(3, 12));
                    $status = 'active';
                }

                $monthlyRent = rand(2000, 8000);

                $contracts[] = [
                    'contract_number' => "UC-{$contractNumber}",
                    'tenant_id' => $tenant->id,
                    'unit_id' => $unit->id,
                    'property_id' => $unit->property_id,
                    'monthly_rent' => $monthlyRent,
                    'security_deposit' => $monthlyRent,
                    'duration_months' => $durationMonths,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'contract_status' => $status,
                    'payment_frequency' => $frequencies[array_rand($frequencies)],
                    'payment_method' => $methods[array_rand($methods)],
                    'grace_period_days' => $gracePeriods[array_rand($gracePeriods)],
                    'late_fee_rate' => rand(0, 5),
                    'utilities_included' => rand(0, 1),
                    'furnished' => rand(0, 1),
                    'evacuation_notice_days' => $evacuationDays[array_rand($evacuationDays)],
                    'created_by' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($contracts) >= $chunkSize) {
                    DB::table('unit_contracts')->insert($contracts);
                    $totalContracts += count($contracts);
                    $contracts = [];
                }
            }

            $progressBar->advance();
        }

        // Insert remaining
        if (! empty($contracts)) {
            DB::table('unit_contracts')->insert($contracts);
            $totalContracts += count($contracts);
        }

        $progressBar->finish();
        $this->newLine();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->info("Created {$totalContracts} contracts successfully!");

        return 0;
    }
}
