<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomLoginController
{
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required'],
            'password' => ['required'],
        ]);

        $login = $credentials['login'];
        $password = $credentials['password'];

        // تحديد نوع الحقل (email أو username)
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // محاولة تسجيل الدخول
        if (Auth::attempt([$field => $login, 'password' => $password], $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended('/admin');
        }

        // إذا فشل، حاول بالحقل الآخر
        $altField = $field === 'email' ? 'username' : 'email';
        if (Auth::attempt([$altField => $login, 'password' => $password], $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended('/admin');
        }

        return back()->withErrors([
            'login' => 'بيانات الدخول غير صحيحة.',
        ])->onlyInput('login');
    }
}