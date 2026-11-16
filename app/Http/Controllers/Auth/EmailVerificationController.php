<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, EmailVerificationService $verification): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:100'],
            'code' => ['required', 'digits:6'],
        ]);

        $user = User::where('email', $validated['email'])->whereNull('email_verified_at')->first();

        if (! $user || ! $verification->verify($user, $validated['code'])) {
            return response()->json([
                'message' => __('auth.verification.invalid_code'),
                'errors' => ['code' => [__('auth.verification.invalid_code')]],
            ], 401);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['redirect' => route('home')]);
    }

    public function resend(Request $request, EmailVerificationService $verification): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:100'],
        ]);

        $user = User::where('email', $validated['email'])->whereNull('email_verified_at')->first();

        if ($user) {
            try {
                $verification->send($user);
            } catch (Throwable $exception) {
                report($exception);

                return response()->json(['message' => __('auth.verification.send_failed')], 503);
            }
        }

        // Same answer whether or not the account exists, to avoid leaking registered emails.
        return response()->json(['message' => __('auth.verification.resent')]);
    }
}
