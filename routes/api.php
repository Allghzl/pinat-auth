<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OAuthController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
    Route::post('/refresh',  [AuthController::class, 'refresh']);

    // OAuth
    Route::get('/oauth/{provider}',          [OAuthController::class, 'redirect']);
    Route::get('/oauth/{provider}/callback', [OAuthController::class, 'callback']);
});

// Protected routes
Route::middleware('auth:api')->prefix('auth')->group(function () {
    Route::get('/me',      [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
