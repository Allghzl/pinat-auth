<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

// Auth routes
Route::middleware('guest')->group(function () {
    Route::inertia('/login', 'auth/login')->name('login');
    Route::inertia('/register', 'auth/register')->name('register');
    Route::inertia('/auth/callback', 'auth/oauth-callback')->name('oauth.callback');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
