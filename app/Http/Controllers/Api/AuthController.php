<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AvatarStorageService;
use App\Services\UserAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected UserAuthService $auth,
    ) {}

    /**
     * Register user baru.
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $data['device'] = $request->userAgent();

        return response()->json(
            $this->auth->register($data),
            201
        );
    }

    /**
     * Login menggunakan email & password.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials['device'] = $request->userAgent();

        return response()->json(
            $this->auth->login($credentials)
        );
    }

    /**
     * Refresh access token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        return response()->json(
            $this->auth->refresh(
                $request->refresh_token,
                $request->userAgent(),
            )
        );
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $token = $this->extractBearerToken($request);

        $user = $this->auth->resolveUser($token);

        if ($user->avatar_key) {
            $user->avatar_url = Storage::disk('s3')->temporaryUrl(
                $user->avatar_key,
                now()->addMinutes(10),
            );
        } else {
            $user->avatar_url = null;
        }

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Establish web session (optional).
     */
    public function establishSession(Request $request): JsonResponse
    {
        $token = $this->extractBearerToken($request);

        $user = $this->auth->resolveUser($token);

        auth('web')->login($user);

        return response()->json([
            'ok' => true,
        ]);
    }

    public function avatar(Request $request): JsonResponse
    {
        $token = $this->extractBearerToken($request);

        $user = $this->auth->resolveUser($token);

        $url = app(AvatarStorageService::class)->getUrl($user);

        return response()->json([
            'url' => $url,
        ]);
    }

    /**
     * Logout current session.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => ['required', 'string'],
            'client_id'     => ['nullable', 'string'],
            'redirect_uri'  => ['nullable', 'url'],
        ]);

        $this->auth->logout($request->refresh_token);

        $redirectTo = null;

        if ($request->filled('redirect_uri')) {
            /**
             * TODO:
             * Validasi redirect_uri berdasarkan client_id.
             * Untuk sementara langsung return dulu.
             */
            $redirectTo = $request->redirect_uri;
        }

        return response()->json([
            'message'      => 'Logged out.',
            'redirect_to'  => $redirectTo,
        ]);
    }

    /**
     * Logout all devices.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->validate([
            'client_id'    => ['nullable', 'string'],
            'redirect_uri' => ['nullable', 'url'],
        ]);

        $token = $this->extractBearerToken($request);

        $user = $this->auth->resolveUser($token);

        $count = $this->auth->logoutAll($user);

        $redirectTo = null;

        if ($request->filled('redirect_uri')) {
            $redirectTo = $request->redirect_uri;
        }

        return response()->json([
            'message' => 'Logged out from all devices.',
            'revoked_sessions' => $count,
            'redirect_to' => $redirectTo,
        ]);
    }

    /**
     * Extract bearer token.
     */
    protected function extractBearerToken(Request $request): string
    {
        $token = $request->bearerToken();

        if (! $token) {
            throw ValidationException::withMessages([
                'token' => ['Bearer token is required.'],
            ]);
        }

        return $token;
    }
}
