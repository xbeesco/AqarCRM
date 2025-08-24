<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;

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