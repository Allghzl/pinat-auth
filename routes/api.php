<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OAuthController;
use App\Http\Controllers\Api\ServiceAuthController;
use App\Http\Controllers\JwksController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Public auth routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
    Route::post('/refresh',  [AuthController::class, 'refresh']);

    // // OAuth
    // Route::get('/oauth/{provider}',          [OAuthController::class, 'redirect']);
    // Route::get('/oauth/{provider}/callback', [OAuthController::class, 'callback']);
});

// Protected routes
Route::middleware('jwt')->prefix('auth')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/avatar', [AuthController::class, 'avatar']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
});
// Service routes
Route::prefix('service')->group(function () {
    Route::post('token', [ServiceAuthController::class, 'login']);
});
