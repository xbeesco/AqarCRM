<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class RestrictTrashedAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return redirect()->route('filament.admin.auth.login');
        }

        // Check if this is a request for trashed/deleted records
        $isTrashedRequest = $this->isTrashedRequest($request);
        
        if ($isTrashedRequest && !Gate::allows('view-trashed-records')) {
            Log::warning('Unauthorized trashed records access attempt', [
                'user_id' => $user->id,
                'user_type' => $user->type,
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
            ]);
            
            abort(403, 'ليس لديك صلاحية لعرض البيانات المحذوفة');
        }

        // Check for modification of deleted records
        $isModifyingTrashed = $this->isModifyingTrashedRequest($request);
        
        if ($isModifyingTrashed && !Gate::allows('modify-deleted-records')) {
            Log::warning('Unauthorized trashed records modification attempt', [
                'user_id' => $user->id,
                'user_type' => $user->type,
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
            ]);
            
            abort(403, 'ليس لديك صلاحية لتعديل البيانات المحذوفة');
        }

        return $next($request);
    }

    /**
     * Check if request is for trashed records
     */
    private function isTrashedRequest(Request $request): bool
    {
        $url = $request->url();
        $query = $request->query();
        
        // Check URL patterns that might indicate trashed records access
        $trashedPatterns = [
            '/trashed',
            '/deleted',
            'activeTab=trashed',
            'filter[trashed]',
        ];
        
        foreach ($trashedPatterns as $pattern) {
            if (str_contains($url, $pattern) || str_contains($request->getQueryString() ?? '', $pattern)) {
                return true;
            }
        }
        
        // Check query parameters
        return isset($query['trashed']) || 
               isset($query['deleted']) ||
               (isset($query['tableFilters']) && str_contains($query['tableFilters'], 'trashed'));
    }

    /**
     * Check if request is modifying trashed records
     */
    private function isModifyingTrashedRequest(Request $request): bool
    {
        // Only check for POST, PUT, PATCH, DELETE requests
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return false;
        }
        
        $url = $request->url();
        
        // Check for restore or force delete actions
        $modifyingPatterns = [
            '/restore',
            '/force-delete',
            'action=restore',
            'action=forceDelete',
        ];
        
        foreach ($modifyingPatterns as $pattern) {
            if (str_contains($url, $pattern)) {
                return true;
            }
        }
        
        return false;
    }
}