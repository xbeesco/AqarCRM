<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\UnitContract;
use App\Models\PropertyContract;
use App\Observers\UnitContractObserver;
use App\Observers\PropertyContractObserver;
use App\Policies\PropertyContractPolicy;
use App\Policies\UnitContractPolicy;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set Test Date if configured in .env
        $this->configureTestDate();
        
        // Register Policies
        Gate::policy(PropertyContract::class, PropertyContractPolicy::class);
        Gate::policy(UnitContract::class, UnitContractPolicy::class);
        
        // Register Observers
        UnitContract::observe(UnitContractObserver::class);
        PropertyContract::observe(PropertyContractObserver::class);
    }
    
    /**
     * Configure Carbon test date from environment variable
     */
    protected function configureTestDate(): void
    {
        $testDate = env('TEST_DATE');
        
        if (!empty($testDate)) {
            try {
                // Parse the test date and set it as "now" for Carbon
                $parsedDate = Carbon::parse($testDate);
                Carbon::setTestNow($parsedDate);
                
                // Log the test date for debugging
                if (config('app.debug')) {
                    logger()->info('Test date configured', [
                        'test_date' => $testDate,
                        'parsed' => $parsedDate->toDateTimeString(),
                        'now' => Carbon::now()->toDateTimeString(),
                    ]);
                }
            } catch (\Exception $e) {
                // Throw a fatal error to stop the system if TEST_DATE format is invalid
                throw new \RuntimeException(
                    "خطأ في تنسيق TEST_DATE في ملف .env\n" .
                    "القيمة المدخلة: '{$testDate}'\n" .
                    "الخطأ: {$e->getMessage()}\n" .
                    "الصيغة المطلوبة: YYYYاولا-MM-DD HH:MM:SS أو أي صيغة يفهمها Carbon\n" .
                    "مثال صحيح: TEST_DATE=\"2025-02-15 14:30:00\"\n" .
                    "لإلغاء وضع الاختبار، اترك TEST_DATE فارغة"
                );
            }
        } else {
            // Ensure we're using real time when TEST_DATE is not set
            Carbon::setTestNow(null);
        }
    }
}
