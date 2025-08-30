<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Models\Setting;

class DateHelper
{
    /**
     * Get the current date (either test date or real date)
     */
    public static function getCurrentDate(): Carbon
    {
        $testDate = self::getTestDate();
        
        if ($testDate) {
            return Carbon::parse($testDate);
        }
        
        return Carbon::now();
    }
    
    /**
     * Get test date from settings or environment
     */
    public static function getTestDate(): ?string
    {
        try {
            // Check database first
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
        $envDate = env('TEST_DATE');
        if (!empty($envDate)) {
            return $envDate;
        }
        
        return null;
    }
    
    /**
     * Check if we're in test mode
     */
    public static function isTestMode(): bool
    {
        return !empty(self::getTestDate());
    }
    
    /**
     * Format date for display
     */
    public static function formatDate($date = null, $format = 'Y-m-d'): string
    {
        if ($date === null) {
            $date = self::getCurrentDate();
        } elseif (!($date instanceof Carbon)) {
            $date = Carbon::parse($date);
        }
        
        return $date->format($format);
    }
}