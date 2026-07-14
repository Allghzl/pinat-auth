<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Services\AvatarStorageService;

class OAuthController extends Controller
{
    private const SUPPORTED = ['google', 'github'];
    private AvatarStorageService $avatarStorageService;

    public function __construct(AvatarStorageService $avatarStorageService)
    {
        $this->avatarStorageService = $avatarStorageService;
    }

    public function redirect(string $provider): RedirectResponse|JsonResponse
    {
        if (! in_array($provider, self::SUPPORTED)) {
            return response()->json(['message' => 'Unsupported provider'], 422);
        }

        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function callback(string $provider, Request $request): JsonResponse|RedirectResponse
    {
        if (! in_array($provider, self::SUPPORTED)) {
            return response()->json(['message' => 'Unsupported provider'], 422);
        }

        try {
            $social = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception) {
            return response()->json(['message' => 'OAuth authentication failed'], 401);
        }

        $oauth = \App\Models\OAuthProvider::where('provider', $provider)
            ->where('provider_id', $social->getId())
            ->first();

        if ($oauth) {
            $user = $oauth->user;
            $oauth->update([
                'access_token'     => $social->token,
                'refresh_token'    => $social->refreshToken,
                'token_expires_at' => $social->expiresIn ? now()->addSeconds($social->expiresIn) : null,
            ]);
        } else {
            $user = User::firstOrCreate(
                ['email' => $social->getEmail()],
                [
                    'name'              => $social->getName() ?? $social->getNickname() ?? 'User',
                    'email_verified_at' => now(),
                ]
            );

            if (!$user->avatar_key && $social->getAvatar()) {
                $objectKey = $this->avatarStorageService
                    ->importFromUrl(
                        $social->getAvatar(),
                        $user
                    );
                $user->update([
                    'avatar_key' => $objectKey
                ]);
            }

            $user->oauthProviders()->create([
                'provider'         => $provider,
                'provider_id'      => $social->getId(),
                'access_token'     => $social->token,
                'refresh_token'    => $social->refreshToken,
                'token_expires_at' => $social->expiresIn ? now()->addSeconds($social->expiresIn) : null,
            ]);
        }

        $raw = Str::random(80);
        $user->refreshTokens()->create([
            'token'      => hash('sha256', $raw),
            'device'     => $request->userAgent(),
            'expires_at' => now()->addMinutes((int) config('jwt.refresh_ttl')),
        ]);

        $accessToken = JWTAuth::fromUser($user);
        $ttl         = config('jwt.ttl') * 60;

        // Redirect to frontend callback page with tokens in query string.
        // ponytail: move to fragment (#) or postMessage if XSS is a concern.
        return redirect()->away(
            url('/auth/callback') . '?' . http_build_query([
                'token'      => $accessToken,
                'refresh'    => $raw,
                'expires_in' => $ttl,
            ])
        );
    }
}
