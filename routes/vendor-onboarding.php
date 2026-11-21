<?php

use App\Http\Controllers\VendorOnboardingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('account/vendor-applications')->name('vendor-applications.')->group(function () {
    Route::get('/', [VendorOnboardingController::class, 'index'])->name('index');
    Route::get('/create', [VendorOnboardingController::class, 'create'])->name('create');
    Route::post('/', [VendorOnboardingController::class, 'store'])->name('store');
    Route::get('/{application}', [VendorOnboardingController::class, 'show'])->whereNumber('application')->name('show');
    Route::patch('/{application}', [VendorOnboardingController::class, 'update'])->whereNumber('application')->name('update');
    Route::post('/{application}/submit', [VendorOnboardingController::class, 'submit'])->whereNumber('application')->name('submit');
});
