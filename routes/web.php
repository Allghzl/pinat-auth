<?php

use App\Http\Controllers\JwksController;
use Illuminate\Support\Facades\Route;

// JWKS endpoint for JWT verification (public, no auth required)
Route::get('/.well-known/jwks.json', JwksController::class);

Route::inertia('/', 'welcome')->name('home');

// Auth routes
Route::middleware('guest')->group(function () {
    Route::inertia('/login', 'auth/login')->name('login');
    Route::inertia('/register', 'auth/register')->name('register');
});

Route::get('/auth/session', function (\Illuminate\Http\Request $request) {
    try {
        $user = \Tymon\JWTAuth\Facades\JWTAuth::setToken($request->get('token'))->authenticate();
    } catch (\Exception) {
        return redirect('/login?error=session_expired');
    }

    if (! $user) {
        return redirect('/login?error=session_expired');
    }

    auth('web')->login($user);
    $request->session()->regenerate();

    return redirect('/dashboard');
})->name('auth.session');

Route::inertia('/auth/callback', 'auth/oauth-callback')->name('oauth.callback');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__ . '/settings.php';
