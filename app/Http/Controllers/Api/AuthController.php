<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return response()->json([
            'user'          => $user,
            'access_token'  => JWTAuth::fromUser($user),
            'refresh_token' => $this->issueRefreshToken($user, $request),
            'token_type'    => 'bearer',
            'expires_in'    => config('jwt.ttl') * 60,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        /** @var User $user */
        $user = auth('api')->user();

        return response()->json([
            'user'          => $user,
            'access_token'  => $token,
            'refresh_token' => $this->issueRefreshToken($user, $request),
            'token_type'    => 'bearer',
            'expires_in'    => config('jwt.ttl') * 60,
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->validate(['refresh_token' => 'required|string']);

        $record = RefreshToken::where('token', hash('sha256', $request->refresh_token))->first();

        if (! $record || ! $record->isValid()) {
            return response()->json(['message' => 'Invalid or expired refresh token'], 401);
        }

        $record->update(['revoked_at' => now()]);
        $user = $record->user;

        return response()->json([
            'access_token'  => JWTAuth::fromUser($user),
            'refresh_token' => $this->issueRefreshToken($user, $request),
            'token_type'    => 'bearer',
            'expires_in'    => config('jwt.ttl') * 60,
        ]);
    }

    public function me(): JsonResponse
    {
        return response()->json(auth('api')->user());
    }

    public function logout(Request $request): JsonResponse
    {
        if ($request->refresh_token) {
            RefreshToken::where('token', hash('sha256', $request->refresh_token))
                ->update(['revoked_at' => now()]);
        }

        auth('api')->logout();

        return response()->json(['message' => 'Logged out']);
    }

    private function issueRefreshToken(User $user, Request $request): string
    {
        $raw = Str::random(80);

        $user->refreshTokens()->create([
            'token'      => hash('sha256', $raw),
            'device'     => $request->userAgent(),
            'expires_at' => now()->addMinutes(config('jwt.refresh_ttl')),
        ]);

        return $raw;
    }
}
