<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('auth.pages.forgot-password');
    }

    public function store(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink(
            $request->safe()->only('email')
        );

        logger()->info('password reset broker result', [
            'status' => $status,
        ]);

        return response()->json([
            'message' => __('passwords.requested'),
        ]);
    }
}
