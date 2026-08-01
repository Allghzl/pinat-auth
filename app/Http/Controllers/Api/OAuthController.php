<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OAuthProvider;
use App\Models\User;
use App\Services\AvatarStorageService;
use App\Services\UserAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    private const SUPPORTED = [
        'google',
        'github',
    ];

    public function __construct(
        protected AvatarStorageService $avatarStorageService,
        protected UserAuthService $auth,
    ) {}

    public function redirect(string $provider, Request $request): RedirectResponse|JsonResponse
    {
        logger()->info('REDIRECT', [
            'session_id' => session()->getId(),
            'session' => session()->all(),
        ]);
        if (! in_array($provider, self::SUPPORTED, true)) {
            return response()->json([
                'message' => 'Unsupported provider',
            ], 422);
        }

        $request->validate([
            'client_id'    => ['nullable', 'string'],
            'redirect_uri' => ['nullable', 'url'],
            'state'        => ['nullable', 'string'],
        ]);

        session([
            'oauth.client_id'    => $request->query('client_id'),
            'oauth.redirect_uri' => $request->query('redirect_uri'),
            'oauth.state'        => $request->query('state'),
        ]);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider, Request $request): JsonResponse|RedirectResponse
    {
        logger()->info('CALLBACK', [
            'session_id' => session()->getId(),
            'session' => session()->all(),
        ]);
        if (! in_array($provider, self::SUPPORTED, true)) {
            return response()->json([
                'message' => 'Unsupported provider',
            ], 422);
        }

        $clientId    = session('oauth.client_id');
        $redirectUri = session('oauth.redirect_uri');
        $state       = session('oauth.state');

        session()->forget([
            'oauth.client_id',
            'oauth.redirect_uri',
            'oauth.state',
        ]);

        try {
            $social = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'OAuth authentication failed',
                'error'   => $e->getMessage(),
            ], 500);
        }

        $oauth = OAuthProvider::query()
            ->where('provider', $provider)
            ->where('provider_id', $social->getId())
            ->first();

        if ($oauth) {

            $user = $oauth->user;

            $oauth->update([
                'access_token' => $social->token,
                'refresh_token' => $social->refreshToken,
                'token_expires_at' => $social->expiresIn
                    ? now()->addSeconds($social->expiresIn)
                    : null,
            ]);
        } else {

            $user = User::firstOrCreate(
                [
                    'email' => $social->getEmail(),
                ],
                [
                    'name' => $social->getName()
                        ?? $social->getNickname()
                        ?? 'User',

                    'email_verified_at' => now(),
                ],
            );

            if (! $user->avatar_key && $social->getAvatar()) {

                $avatarKey = $this->avatarStorageService->importFromUrl(
                    $social->getAvatar(),
                    $user,
                );

                $user->update([
                    'avatar_key' => $avatarKey,
                ]);
            }

            $user->oauthProviders()->create([
                'provider' => $provider,
                'provider_id' => $social->getId(),
                'access_token' => $social->token,
                'refresh_token' => $social->refreshToken,
                'token_expires_at' => $social->expiresIn
                    ? now()->addSeconds($social->expiresIn)
                    : null,
            ]);
        }

        $result = $this->auth->oauthLogin(
            $user,
            $request->userAgent(),
        );

        /*
        |--------------------------------------------------------------------------
        | Redirect ke Frontend Callback
        |--------------------------------------------------------------------------
        |
        | Semua flow login ditangani oleh callback.tsx.
        | Callback akan:
        | - mengambil data user
        | - menyimpan account switcher
        | - redirect ke client (SSO)
        | - atau login web PinatAuth
        |
        */

        $params = http_build_query([
            'access_token'  => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
            'session_id'    => $result['session_id'],
            'expires_in'    => $result['expires_in'],

            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
        ]);

        return redirect("/auth/callback?$params");
    }
}
