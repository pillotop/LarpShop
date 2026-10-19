<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create()
    {
        return view('login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            if ($request->expectsJson()) {
                return response()->json([
                    'redirect' => route('home'),
                ]);
            }

            return redirect()->intended(route('home'));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'The provided credentials are incorrect',
                'errors' => [
                    'email' => ['The provided credentials are incorrect.'],
                    'password' => [ 'The provided credentials are incorrect.' ],
                ],
            ], 401);
        }

        return back()->withErrors([
            'email' => 'The provided credentials are incorrect.',
        ])->onlyInput('email');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
