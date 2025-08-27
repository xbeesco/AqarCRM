<?php

namespace App\Console\Commands;

use App\Models\UnitContract;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateExpiredContracts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:update-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'تحديث حالة العقود المنتهية تلقائياً';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('بدء تحديث العقود المنتهية...');
        
        // Find active contracts that have expired
        $expiredContracts = UnitContract::where('contract_status', 'active')
            ->where('end_date', '<', Carbon::now())
            ->get();
        
        if ($expiredContracts->isEmpty()) {
            $this->info('لا توجد عقود منتهية للتحديث');
            return Command::SUCCESS;
        }
        
        $count = 0;
        foreach ($expiredContracts as $contract) {
            $contract->update(['contract_status' => 'expired']);
            $count++;
            $this->line("✅ تم تحديث العقد #{$contract->id} - {$contract->contract_number}");
        }
        
        $this->info("تم تحديث {$count} عقد منتهي بنجاح");
        
        // Check for contracts expiring soon (within 7 days)
        $expiringSoon = UnitContract::expiringSoon(7)->get();
        
        if ($expiringSoon->isNotEmpty()) {
            $this->warn("\n⚠️ يوجد {$expiringSoon->count()} عقد سينتهي خلال 7 أيام:");
            foreach ($expiringSoon as $contract) {
                $days = $contract->getRemainingDays();
                $this->warn("   - العقد #{$contract->id}: متبقي {$days} يوم");
            }
        }
        
        return Command::SUCCESS;
    }
}