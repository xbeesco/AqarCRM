<?php

namespace App\Console\Commands;

use App\Models\Location;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RegenerateLocationPaths extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'location:regenerate-paths {--force : Force regeneration without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate hierarchical paths for all locations to ensure proper ordering';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        
        if (!$force && !$this->confirm('This will regenerate all location paths. Continue?')) {
            $this->info('Operation cancelled.');
            return;
        }
        
        $this->info('Starting location path regeneration...');
        
        try {
            DB::beginTransaction();
            
            // Clear all paths first
            $this->info('Clearing existing paths...');
            Location::query()->update(['path' => null]);
            
            $totalProcessed = 0;
            
            // Process level by level to ensure parent paths exist first
            for ($level = 1; $level <= 4; $level++) {
                $this->info("Processing level {$level}...");
                
                $locations = Location::where('level', $level)->orderBy('id')->get();
                $count = $locations->count();
                
                if ($count > 0) {
                    $progressBar = $this->output->createProgressBar($count);
                    $progressBar->start();
                    
                    foreach ($locations as $location) {
                        $location->updatePath();
                        $location->saveQuietly(); // Use saveQuietly to avoid triggering events
                        $progressBar->advance();
                        $totalProcessed++;
                    }
                    
                    $progressBar->finish();
                    $this->newLine();
                }
            }
            
            DB::commit();
            
            $this->info("Successfully regenerated paths for {$totalProcessed} locations!");
            
            // Verify results
            $this->info('Verifying hierarchy...');
            $this->verifyHierarchy();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error regenerating paths: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Verify that the hierarchy is correct
     */
    private function verifyHierarchy(): void
    {
        $locations = Location::orderBy('path')->get();
        $errors = 0;
        
        foreach ($locations as $location) {
            if ($location->parent_id) {
                $parent = Location::find($location->parent_id);
                if (!$parent || !str_starts_with($location->path, $parent->path)) {
                    $this->error("❌ Location '{$location->name}' has incorrect path relative to parent!");
                    $errors++;
                }
            }
        }
        
        if ($errors === 0) {
            $this->info('✅ All locations have correct hierarchical paths!');
        } else {
            $this->error("❌ Found {$errors} locations with incorrect paths!");
        }
    }
}
