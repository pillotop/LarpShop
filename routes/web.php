<?php

use Illuminate\Support\Facades\Route;

Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/', function () {
    return view('homepage');
});

Route::get('/login', function () {
    return view('login');
});

Route::get('/product', function () {
    return view('product');
});

Route::get('/categories/{category}', function (string $category) {
    return view('categories.category', [
        'category' => $category,
    ]);
});

Route::get('/supports', function () {
    return view('supports');
});
