<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    public function create(string $token): View
    {
        return view('auth.pages.reset-password', [
            'token' => $token,
            'email' => request()->query('email', ''),
        ]);
    }

    public function store(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->safe()->only([
                'token', 'email', 'password', 'password_confirmation',
            ]),
            function (User $user, string $password): void {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->setRememberToken(Str::random());
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PasswordReset) {
            return response()->json([
                'message' => __('passwords.unable'),
                'errors' => ['email' => [__('passwords.invalid_link')]],
            ], 401);
        }

        return response()->json([
            'message' => __('passwords.completed'),
            'redirect' => route('login'),
        ]);
    }
}
