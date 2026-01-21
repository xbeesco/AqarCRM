<?php

namespace App\Helpers;

class AppHelper
{
    /**
     * Get domain from APP_URL
     */
    public static function getEmailDomain(): string
    {
        $appUrl = config('app.url', 'localhost');

        $domain = preg_replace('#^https?://#', '', $appUrl);
        $domain = rtrim($domain, '/');
        $domain = explode('/', $domain)[0];
        $domain = explode(':', $domain)[0];

        return $domain ?: 'localhost';
    }
}
