<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class CheckUserPermissions
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission = null): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return redirect()->route('filament.admin.auth.login');
        }

        // Log access attempt
        Log::info('Admin panel access attempt', [
            'user_id' => $user->id,
            'user_type' => $user->type,
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Check general admin panel access
        if (!Gate::allows('access-admin-panel')) {
            Log::warning('Unauthorized admin panel access blocked', [
                'user_id' => $user->id,
                'user_type' => $user->type,
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
            ]);
            
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        // Check specific permission if provided
        if ($permission && !Gate::allows($permission)) {
            Log::warning('Insufficient permissions', [
                'user_id' => $user->id,
                'user_type' => $user->type,
                'required_permission' => $permission,
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
            ]);
            
            abort(403, 'ليس لديك صلاحية كافية لهذا الإجراء');
        }

        return $next($request);
    }
}