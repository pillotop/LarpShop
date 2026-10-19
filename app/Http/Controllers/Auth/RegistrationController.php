<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RegistrationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email'     => ['required', 'email', 'max:100', 'unique:users,email'],
            'username'  => ['required', 'string', 'max:50', 'unique:users,username'],
            'password'  => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        $user = new User();

        $user->username = $validated['username'];
        $user->email    = $validated['email'];
        $user->password = $validated['password'];
        $user->role     = 'customer';
        $user->save();

        Auth::login($user);

        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json([
                'redirect' => route('home'),
            ], 201);
        }

        return redirect()->intended(route('home'));
    }
}
