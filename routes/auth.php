<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\User;
use Livewire\Volt\Volt;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\PasswordResetController;

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
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])
        ->name('logout');

    Route::get('/password-reset', [PasswordResetController::class, 'create'])
        ->name('password-reset');

    Route::post('/password-reset', [PasswordResetController::class, 'store'])
        ->name('password-reset.store');
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
