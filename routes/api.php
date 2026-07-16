<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OAuthController;
use App\Http\Controllers\Api\ServiceAuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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

// Service routes
Route::prefix('service')->group(function () {
    Route::post('token', [ServiceAuthController::class, 'login']);
});
