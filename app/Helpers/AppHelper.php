<?php

namespace App\Helpers;

class AppHelper
{
    /**
     * Get domain from APP_URL for email generation
     * Removes http/https and trailing slashes
     */
    public static function getEmailDomain(): string
    {
        $appUrl = config('app.url', 'localhost');
        
        // Remove protocol (http:// or https://)
        $domain = preg_replace('#^https?://#', '', $appUrl);
        
        // Remove trailing slash
        $domain = rtrim($domain, '/');
        
        // Remove any path after domain
        $domain = explode('/', $domain)[0];
        
        // Remove port if exists (e.g., localhost:8000)
        $domain = explode(':', $domain)[0];
        
        return $domain ?: 'localhost';
    }
    
    /**
     * Generate email from phone number
     */
    public static function generateEmailFromPhone(string $phone): string
    {
        $domain = self::getEmailDomain();
        return $phone . '@' . $domain;
    }
}