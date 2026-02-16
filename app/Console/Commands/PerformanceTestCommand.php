<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Models\Unit;
use App\Models\UnitContract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PerformanceTestCommand extends Command
{
    protected $signature = 'test:performance
                            {--seed : Run the seeder first}
                            {--limit=50 : Number of properties to load}';

    protected $description = 'Run performance tests for occupancy rate queries';

    public function handle(): int
    {
        if ($this->option('seed')) {
            $this->info('Running Performance Seeder...');
            $this->call('db:seed', ['--class' => 'PerformanceTestSeeder']);
        }

        $this->info('');
        $this->info('=== Performance Test Results ===');
        $this->newLine();

        // Show current data counts
        $this->showDataCounts();

        $limit = (int) $this->option('limit');

        // Test 1: Current query (with subquery for each property)
        $this->testCurrentOccupancyQuery($limit);

        // Test 2: Optimized with raw SQL subqueries
        $this->testOptimizedRawQuery($limit);

        // Test 3: Using JOIN approach
        $this->testJoinApproach($limit);

        // Test 4: Check index usage
        $this->analyzeIndexUsage();

        // Recommendations
        $this->showRecommendations();

        return Command::SUCCESS;
    }

    private function showDataCounts(): void
    {
        $this->info('📊 Data Counts:');
        $this->table(
            ['Table', 'Count'],
            [
                ['properties', number_format(Property::count())],
                ['units', number_format(Unit::count())],
                ['unit_contracts', number_format(UnitContract::count())],
                ['active_contracts', number_format(UnitContract::where('contract_status', 'active')
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->count())],
            ]
        );
        $this->newLine();
    }

    private function testCurrentOccupancyQuery(int $limit): void
    {
        $this->info("🔍 Test 1: Current Query (withCount + whereHas) - {$limit} properties");

        DB::flushQueryLog();
        DB::enableQueryLog();

        $start = microtime(true);

        $properties = Property::query()
            ->with(['owner', 'location'])
            ->withCount('units as total_units')
            ->withCount(['units as occupied_units' => function ($q) {
                $q->whereHas('activeContract');
            }])
            ->limit($limit)
            ->get();

        $duration = round((microtime(true) - $start) * 1000, 2);
        $queries = DB::getQueryLog();

        $this->line("  ⏱️  Duration: {$duration}ms");
        $this->line('  📝 Queries: '.count($queries));
        $this->line('  📦 Properties loaded: '.$properties->count());

        // Sample occupancy rates
        if ($properties->isNotEmpty()) {
            $sample = $properties->first();
            $rate = $sample->total_units > 0
                ? round(($sample->occupied_units / $sample->total_units) * 100)
                : 0;
            $this->line("  📈 Sample: {$sample->name} - {$sample->occupied_units}/{$sample->total_units} ({$rate}%)");
        }
        $this->newLine();
    }

    private function testOptimizedRawQuery(int $limit): void
    {
        $this->info("🚀 Test 2: Optimized Raw SQL Subqueries - {$limit} properties");

        DB::flushQueryLog();
        DB::enableQueryLog();

        $start = microtime(true);

        $now = now()->format('Y-m-d');

        $properties = DB::table('properties')
            ->select([
                'properties.id',
                'properties.name',
                'properties.owner_id',
            ])
            ->selectRaw('(SELECT COUNT(*) FROM units WHERE units.property_id = properties.id) as total_units')
            ->selectRaw("(SELECT COUNT(DISTINCT units.id) FROM units
                          INNER JOIN unit_contracts ON unit_contracts.unit_id = units.id
                          WHERE units.property_id = properties.id
                          AND unit_contracts.contract_status = 'active'
                          AND unit_contracts.start_date <= '{$now}'
                          AND unit_contracts.end_date >= '{$now}') as occupied_units")
            ->limit($limit)
            ->get();

        $duration = round((microtime(true) - $start) * 1000, 2);
        $queries = DB::getQueryLog();

        $this->line("  ⏱️  Duration: {$duration}ms");
        $this->line('  📝 Queries: '.count($queries));
        $this->line('  📦 Properties loaded: '.$properties->count());

        if ($properties->isNotEmpty()) {
            $sample = $properties->first();
            $rate = $sample->total_units > 0
                ? round(($sample->occupied_units / $sample->total_units) * 100)
                : 0;
            $this->line("  📈 Sample: {$sample->name} - {$sample->occupied_units}/{$sample->total_units} ({$rate}%)");
        }
        $this->newLine();
    }

    private function testJoinApproach(int $limit): void
    {
        $this->info("⚡ Test 3: JOIN with GROUP BY - {$limit} properties");

        DB::flushQueryLog();
        DB::enableQueryLog();

        $start = microtime(true);

        $now = now()->format('Y-m-d');

        $properties = DB::table('properties')
            ->leftJoin('units', 'units.property_id', '=', 'properties.id')
            ->leftJoin('unit_contracts', function ($join) use ($now) {
                $join->on('unit_contracts.unit_id', '=', 'units.id')
                    ->where('unit_contracts.contract_status', '=', 'active')
                    ->where('unit_contracts.start_date', '<=', $now)
                    ->where('unit_contracts.end_date', '>=', $now);
            })
            ->select([
                'properties.id',
                'properties.name',
                'properties.owner_id',
            ])
            ->selectRaw('COUNT(DISTINCT units.id) as total_units')
            ->selectRaw('COUNT(DISTINCT CASE WHEN unit_contracts.id IS NOT NULL THEN units.id END) as occupied_units')
            ->groupBy('properties.id', 'properties.name', 'properties.owner_id')
            ->limit($limit)
            ->get();

        $duration = round((microtime(true) - $start) * 1000, 2);
        $queries = DB::getQueryLog();

        $this->line("  ⏱️  Duration: {$duration}ms");
        $this->line('  📝 Queries: '.count($queries));
        $this->line('  📦 Properties loaded: '.$properties->count());

        if ($properties->isNotEmpty()) {
            $sample = $properties->first();
            $rate = $sample->total_units > 0
                ? round(($sample->occupied_units / $sample->total_units) * 100)
                : 0;
            $this->line("  📈 Sample: {$sample->name} - {$sample->occupied_units}/{$sample->total_units} ({$rate}%)");
        }
        $this->newLine();
    }

    private function analyzeIndexUsage(): void
    {
        $this->info('📈 Index Analysis:');

        try {
            $explain = DB::select("EXPLAIN SELECT COUNT(DISTINCT units.id)
                FROM units
                INNER JOIN unit_contracts ON unit_contracts.unit_id = units.id
                WHERE units.property_id = 1
                AND unit_contracts.contract_status = 'active'
                AND unit_contracts.start_date <= NOW()
                AND unit_contracts.end_date >= NOW()");

            $this->table(
                ['table', 'type', 'key', 'rows', 'Extra'],
                collect($explain)->map(fn ($row) => [
                    $row->table,
                    $row->type,
                    $row->key ?? 'NULL',
                    $row->rows,
                    substr($row->Extra ?? '', 0, 50),
                ])->toArray()
            );
        } catch (\Exception $e) {
            $this->error('Could not run EXPLAIN: '.$e->getMessage());
        }

        $this->newLine();

        // Show existing indexes
        $this->info('📋 Existing Indexes on unit_contracts:');
        try {
            $indexes = DB::select('SHOW INDEX FROM unit_contracts');
            $indexNames = collect($indexes)->pluck('Key_name')->unique()->values();
            foreach ($indexNames as $name) {
                $cols = collect($indexes)->where('Key_name', $name)->pluck('Column_name')->implode(', ');
                $this->line("  - {$name}: ({$cols})");
            }
        } catch (\Exception $e) {
            $this->error('Could not show indexes: '.$e->getMessage());
        }
        $this->newLine();
    }

    private function showRecommendations(): void
    {
        $this->info('💡 Recommendations:');
        $this->line('  1. Composite index: (unit_id, contract_status, start_date, end_date)');
        $this->line('  2. Consider denormalized "is_occupied" column on units table');
        $this->line('  3. Use database triggers to update occupancy on contract changes');
        $this->line('  4. For very large datasets, consider materialized views');
        $this->newLine();
    }
}
