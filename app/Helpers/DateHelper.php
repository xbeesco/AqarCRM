<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Models\Setting;

class DateHelper
{
    /**
     * Initialize test date on application boot
     */
    public static function initializeTestDate(): void
    {
        $testDate = self::getTestDate();
        $testDate = $testDate ? Carbon::parse($testDate) : null ;
    
        Carbon::setTestNow($testDate);
    }
    
    /**
     * Set a new test date
     */
    public static function setTestDate(?string $date): void
    {
        if ($date) {
            $carbonDate = Carbon::parse($date);
            $realNow = Carbon::now()->setTestNow(null);
            
            // Get session lifetime in minutes and convert to days
            $sessionLifetimeMinutes = config('session.lifetime', 120);
            $maxDaysAllowed = floor($sessionLifetimeMinutes / 60 / 24);
            
            // Check if date exceeds session lifetime range (both past and future)
            $daysDifference = abs($carbonDate->diffInDays($realNow));
            
            if ($daysDifference > $maxDaysAllowed) {
                throw new \InvalidArgumentException(
                    "التاريخ يجب أن يكون في نطاق {$maxDaysAllowed} يوم من التاريخ الحالي (مدة صلاحية الجلسة)"
                );
            }
            
            Carbon::setTestNow($carbonDate);
            Setting::set('test_date', $date);
        } else {
            self::clearTestDate();
        }
    }
    
    /**
     * Clear the test date
     */
    public static function clearTestDate(): void
    {
        Carbon::setTestNow(null);
        Setting::forget('test_date');
    }
    
    /**
     * Get test date from settings or env
     */
    public static function getTestDate(): ?string
    {
        // Check database first
        try {
            if (\Schema::hasTable('settings')) {
                $testDate = Setting::get('test_date');
                if (!empty($testDate)) {
                    return $testDate;
                }
            }
        } catch (\Exception $e) {
            // Ignore database errors
        }
        
        // Fall back to environment variable
        return env('TEST_DATE') ?: null;
    }
    
    /**
     * Check if we're in test mode
     */
    public static function isTestMode(): bool
    {
        return !empty(self::getTestDate());
    }
    
    /**
     * Get status information about test mode
     */
    public static function getTestModeStatus(): array
    {
        $testDate = self::getTestDate();
        
        return [
            'enabled' => !empty($testDate),
            'test_date' => $testDate,
            'current_date' => Carbon::now()->format('Y-m-d H:i:s')
        ];
    }
}