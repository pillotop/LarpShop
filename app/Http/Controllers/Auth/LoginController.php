<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class LoginController extends Controller
{
    public function create(Request $request)
    {
        $request->session()->reflash();

        return redirect()->route('home');
    }

    public function store(Request $request, EmailVerificationService $verification)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials)) {
            if ($verification->isRequired() && ($user = Auth::guard(null)->user()) && $user->email_verified_at === null) {
                Auth::logout();

                try {
                    $verification->send($user);
                } catch (Throwable $exception) {
                    report($exception);
                }

                if ($request->expectsJson()) {
                    return response()->json([
                        'requires_verification' => true,
                        'email' => $user->email,
                        'message' => __('auth.verification.required', ['email' => $user->email]),
                    ], 403);
                }

                return back()->withErrors([
                    'email' => __('auth.verification.required', ['email' => $user->email]),
                ])->onlyInput('email');
            }

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
                'message' => __('auth.invalid_credentials_message'),
                'errors' => [
                    'email' => [__('auth.invalid_credentials')],
                    'password' => [__('auth.invalid_credentials')],
                ],
            ], 401);
        }

        return back()->withErrors([
            'email' => __('auth.invalid_credentials'),
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
