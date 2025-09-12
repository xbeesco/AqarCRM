<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\DateHelper;
use Carbon\Carbon;

class ApplyTestDate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Apply test date at the beginning of each request
        $this->applyTestDate();
        
        return $next($request);
    }
    
    /**
     * Apply the test date for this request
     */
    protected function applyTestDate(): void
    {
        try {
            $testDate = DateHelper::getTestDate();
            
            if ($testDate) {
                Carbon::setTestNow(Carbon::parse($testDate));
            } else {
                Carbon::setTestNow(null);
            }
        } catch (\Exception $e) {
            // If test date fails, use real date
            Carbon::setTestNow(null);
        }
    }
}
