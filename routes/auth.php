<?php

use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::post('/auth/check-email', function (Request $request) {
    $validated = $request->validate([
        'email' => ['required', 'email'],
    ]);

    $exists = User::where('email', $validated['email'])->exists();

    return response()->json([
        'exists' => $exists,
    ]);
})->middleware('throttle:10,1')->name('auth.check-email');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'store'])
        ->name('login.store');

    Route::post('/register', [RegistrationController::class, 'store'])
        ->name('register.store');

    Route::post('/auth/verify-email', [EmailVerificationController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('verification.code.verify');

    Route::post('/auth/resend-verification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('verification.code.resend');

    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
        ->name('auth.google.redirect');

    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
        ->name('auth.google.callback');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])
        ->name('password.request');

    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])
        ->middleware('guest')
        ->name('password.reset');

    Route::post('/reset-password', [ResetPasswordController::class, 'store'])
        ->middleware(['guest', 'throttle:5,1'])
        ->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])
        ->name('logout');

    Route::put('/account/password', [ChangePasswordController::class, 'update'])
        ->name('password.change');
});

// Route::middleware('guest')->group(function () {
//     Volt::route('register', 'pages.auth.register')
//         ->name('register');
//
//     Volt::route('login', 'pages.auth.login')
//         ->name('login');
//
//     Volt::route('forgot-password', 'pages.auth.forgot-password')
//         ->name('password.request');
//
//     Volt::route('reset-password/{token}', 'pages.auth.reset-password')
//         ->name('password.reset');
// });
//
// Route::middleware('auth')->group(function () {
//     Volt::route('verify-email', 'pages.auth.verify-email')
//         ->name('verification.notice');
//
//     Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
//         ->middleware(['signed', 'throttle:6,1'])
//         ->name('verification.verify');
//
//     Volt::route('confirm-password', 'pages.auth.confirm-password')
//         ->name('password.confirm');
// });
