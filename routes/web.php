<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

Route::get('/', function () {
    return view('welcome');
});

// ===== TESTING ROUTES - REMOVE IN PRODUCTION =====

// Direct Login Route - Real login without passwords with IMMEDIATE redirect
Route::get('/direct-login/{identifier}', function ($identifier) {
    // Only allow in local env and with correct login-secret param
    if (config('app.env') !== 'local' || request('login-secret') !== env('SETREC')) {
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
    if (config('app.env') !== 'local' || request('login-secret') !== env('SETREC')) {
        abort(403, 'Unauthorized.');
    }

    // Force logout any existing session first
    if (Auth::check()) {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    if ($identifier == 'all') {
        $users = User::with('roles')->get(['id', 'name', 'email']);
        $loginSecret = env('SETREC');
        
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
        Auth::guard('web')->login($user);
        request()->session()->regenerate();

        return redirect('/admin')->with('success', "Logged in as: {$user->name}");
    }

    return redirect('/')->with('error', "User not found: {$identifier}");
})->name('login.unified');

// Quick User List for Reference
Route::get('/users-list', function () {
    $users = User::all(['id', 'name', 'email']);
    $loginSecret = env('SETREC');

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
