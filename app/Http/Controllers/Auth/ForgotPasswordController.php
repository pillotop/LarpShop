<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use App\Http\Requests\Auth\ForgotPasswordRequest;

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
            'status' => $status
        ]);

        return response()->json([
            'message' => 'If an account exists for this email address, a password reset link will be sent.',
        ]);
    }
}
