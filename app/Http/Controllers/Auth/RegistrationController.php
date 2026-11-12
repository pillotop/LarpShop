<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Throwable;

class RegistrationController extends Controller
{
    public function store(Request $request, EmailVerificationService $verification)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        $user = new User;

        $user->username = $validated['username'];
        $user->email = $validated['email'];
        $user->password = $validated['password'];
        $user->role = 'customer';

        if (! $verification->isRequired()) {
            $user->email_verified_at = now();
            $user->save();

            Auth::login($user);
            $request->session()->regenerate();

            if ($request->expectsJson()) {
                return response()->json(['redirect' => route('home')], 201);
            }

            return redirect()->intended(route('home'));
        }

        $user->save();

        return $this->sendCode($request, $verification, $user);
    }

    private function sendCode(Request $request, EmailVerificationService $verification, User $user): JsonResponse
    {
        try {
            $verification->send($user, respectCooldown: false);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => __('auth.verification.send_failed'),
            ], 503);
        }

        return response()->json([
            'verify' => true,
            'email' => $user->email,
            'message' => __('auth.verification.sent', ['email' => $user->email]),
        ], 202);
    }
}
