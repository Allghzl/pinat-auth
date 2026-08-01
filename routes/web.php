<?php

use App\Http\Controllers\Api\OAuthController;
use App\Http\Controllers\JwksController;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Support\Facades\Route;

// JWKS endpoint for JWT verification (public, no auth required)
Route::get('/.well-known/jwks.json', JwksController::class);

Route::inertia('/', 'welcome')->name('home');

// Auth routes
Route::inertia('/login', 'auth/login')->name('login');
Route::middleware('guest')->group(function () {
    Route::inertia('/register', 'auth/register')->name('register');
});

Route::prefix('api/auth')->group(function () {
    Route::get('/oauth/{provider}',          [OAuthController::class, 'redirect']);
    Route::get('/oauth/{provider}/callback', [OAuthController::class, 'callback']);
});

Route::get('/auth/session', function (\Illuminate\Http\Request $request) {
    try {
        $token = $request->validate([
            'token' => ['required', 'string'],
        ])['token'];

        $payload = app(JwtService::class)->verify($token);

        if (($payload->type ?? null) !== 'user') {
            throw new Exception('Invalid token type.');
        }

        $user = User::find($payload->sub);

        if (! $user) {
            throw new Exception('User not found.');
        }
    } catch (\Throwable) {
        return redirect('/login?error=session_expired');
    }

    auth()->login($user);

    $request->session()->regenerate();

    return redirect('/dashboard');
})->name('auth.session');

Route::inertia('/auth/callback', 'auth/callback')->name('oauth.callback');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__ . '/settings.php';
