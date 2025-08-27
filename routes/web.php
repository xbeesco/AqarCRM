<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Controllers\PropertyPrintController;

Route::get('/', function () {
    return view('welcome');
});

// Property Print Route
Route::get('/property/{property}/print', [PropertyPrintController::class, 'print'])->name('property.print');

// ===== TESTING ROUTES - REMOVE IN PRODUCTION =====

// Direct Login Route - Real login without passwords with IMMEDIATE redirect
Route::get('/direct-login/{identifier}', function ($identifier) {
    // Only allow in local env and with correct login-secret param
    if (config('app.env') !== 'local' || request('login-secret') !== config('app.backdoor_secret')) {
        abort(403, 'Unauthorized.');
    }

    // Force logout any existing session first
    Auth::guard('web')->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    // Find user by ID or email
    $user = is_numeric($identifier) 
        ? User::find($identifier)
        : User::where('email', $identifier)->first();

    if ($user) {
        // Perform actual login
        Auth::guard('web')->login($user);
        request()->session()->regenerate();

        // DIRECT REDIRECT to admin panel
        return redirect('/admin')->with('success', "تم تسجيل الدخول بنجاح كـ: {$user->name}");
    }

    return redirect('/')->with('error', "المستخدم غير موجود: {$identifier}");
})->name('direct.login');

// Simple Login Route 
Route::get('/backdoor/{identifier}', function ($identifier) {
    // Only allow in local env and with correct login-secret param
    $env = config('app.env');
    $secret = request('login-secret');
    // Use config only (which reads from env automatically)
    $expectedSecret = config('app.backdoor_secret');
    
    if ($env !== 'local' || $secret !== $expectedSecret) {
        abort(403, 'Unauthorized.');
    }

    // Force logout any existing session first
    if (Auth::guard('web')->check()) {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    if ($identifier == 'all') {
        $users = User::with('roles')->get(['id', 'name', 'email']);
        $loginSecret = config('app.backdoor_secret');
        
        // Create simple view for user list
        $html = '<html><head><title>Users List</title>';
        $html .= '<style>body{font-family:Arial;margin:20px;}table{border-collapse:collapse;width:100%;}';
        $html .= 'th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background:#f2f2f2;}';
        $html .= 'a{color:#007bff;text-decoration:none;}a:hover{text-decoration:underline;}</style></head>';
        $html .= '<body><h1>Available Users</h1><table>';
        $html .= '<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Quick Login</th></tr>';
        
        foreach ($users as $user) {
            $role = $user->roles->first()->name ?? 'No Role';
            $loginUrl = url("/backdoor/{$user->id}?login-secret={$loginSecret}");
            $html .= "<tr><td>{$user->id}</td><td>{$user->name}</td>";
            $html .= "<td>{$user->email}</td><td>{$role}</td>";
            $html .= "<td><a href='{$loginUrl}'>Login</a></td></tr>";
        }
        
        $html .= '</table></body></html>';
        return response($html);
    }

    // Find user by ID or email
    $user = is_numeric($identifier) 
        ? User::find($identifier)
        : User::where('email', $identifier)->first();

    if ($user) {
        // Login with remember me option
        Auth::guard('web')->login($user, true);
        request()->session()->regenerate();
        
        // Direct redirect to admin panel
        return redirect()->intended('/admin');
    }

    return redirect('/')->with('error', "User not found: {$identifier}");
})->name('login.unified');

// Quick User List for Reference
Route::get('/users-list', function () {
    $users = User::all(['id', 'name', 'email']);
    $loginSecret = config('app.login_secret');

    return response()->json([
        'message' => 'Available users for testing',
        'usage' => 'Use /backdoor/{id|email}?login-secret=' . $loginSecret,
        'examples' => [
            'User by ID: /backdoor/1?login-secret=' . $loginSecret,
            'User by Email: /backdoor/admin@aqarcrm.com?login-secret=' . $loginSecret,
        ],
        'users' => $users->map(function ($user) use ($loginSecret) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'quick_login' => url("/backdoor/{$user->id}?login-secret={$loginSecret}"),
            ];
        })
    ], 200, [], JSON_PRETTY_PRINT);
})->name('users.list');

// Force Logout Route
Route::get('/force-logout', function () {
    $loggedOut = false;
    $userName = '';

    // Check web guard
    if (Auth::guard('web')->check()) {
        $user = Auth::guard('web')->user();
        $userName = $user->name;
        Auth::guard('web')->logout();
        $loggedOut = true;
    }

    if ($loggedOut) {
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/')->with('success', "Logged out: {$userName}");
    }

    return redirect('/')->with('info', 'No active session');
})->name('force.logout');

// Test access route
Route::get('/test-access', function () {
    $user = User::where('email', 'admin@aqarcrm.test')->first();
    
    if (!$user) {
        return 'User not found';
    }
    
    // Login the user
    auth()->login($user);
    
    // Check if logged in
    if (!auth()->check()) {
        return 'Failed to login';
    }
    
    // Check panel access
    $panel = \Filament\Facades\Filament::getPanel('admin');
    $canAccess = $user->canAccessPanel($panel);
    
    return [
        'user' => $user->email,
        'type' => $user->type,
        'authenticated' => auth()->check(),
        'canAccessPanel' => $canAccess,
        'redirect_to_admin' => $canAccess ? 'Should work!' : 'Will get 403'
    ];
});
