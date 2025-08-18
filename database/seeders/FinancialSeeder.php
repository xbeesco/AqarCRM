<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FinancialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            PaymentMethodSeeder::class,
            PaymentStatusSeeder::class,
            RepairCategorySeeder::class,
        ]);
    }
}
