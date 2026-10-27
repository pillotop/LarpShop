<?php

use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\CustomerServicePageController;
use Illuminate\Support\Facades\Route;

Route::get('/about', [StaticPageController::class, 'about'])->name('about');
Route::get('/contact', [StaticPageController::class, 'contact'])->name('contact');
Route::post('/contact', [StaticPageController::class, 'submitContact'])->name('contact.submit');
Route::get('/investors', [StaticPageController::class, 'investors'])->name('investors');
Route::get('/blog', [StaticPageController::class, 'blog'])->name('blog');
Route::get('/press', [StaticPageController::class, 'press'])->name('press');

Route::get('/help', [CustomerServicePageController::class, 'help'])->name('help');
Route::get('/privacy', [CustomerServicePageController::class, 'privacy'])->name('privacy');
Route::get('/community', [CustomerServicePageController::class, 'community'])->name('community');
Route::get('/recalls', [CustomerServicePageController::class, 'recalls'])->name('recalls');
