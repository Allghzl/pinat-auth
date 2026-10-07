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
use Illuminate\Support\Str;
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
        if (! in_array($provider, self::SUPPORTED, true)) {
            return response()->json(['message' => 'Unsupported provider'], 422);
        }

        $request->validate([
            'client_id'    => ['nullable', 'string'],
            'redirect_uri' => ['nullable', 'url'],
            'state'        => ['nullable', 'string'],
        ]);

        // Encode caller params into the state so we don't rely on session cookies.
        // Instagram/Facebook WebViews block cross-origin cookies, which kills session-based state.
        $payload = base64_encode(json_encode([
            'client_id'    => $request->query('client_id'),
            'redirect_uri' => $request->query('redirect_uri'),
            'state'        => $request->query('state'),
            'nonce'        => Str::random(16),
        ]));
        $sig   = hash_hmac('sha256', $payload, config('app.key'));
        $state = $payload . '.' . $sig;

        return Socialite::driver($provider)
            ->with(['state' => $state])
            ->redirect();
    }

    public function callback(string $provider, Request $request): JsonResponse|RedirectResponse
    {
        if (! in_array($provider, self::SUPPORTED, true)) {
            return response()->json(['message' => 'Unsupported provider'], 422);
        }

        // Strip tracking params (fbclid, etc.) injected by Instagram/Facebook WebView
        // before Socialite reads the request — extra params can confuse the OAuth library.
        $allowed = ['code', 'state', 'error', 'error_description', 'scope', 'iss', 'authuser', 'prompt'];
        $request->query->replace($request->query->all());
        foreach (array_keys($request->query->all()) as $key) {
            if (! in_array($key, $allowed, true)) {
                $request->query->remove($key);
            }
        }

        // Decode caller params from stateless signed state (no session cookie needed)
        $clientId    = null;
        $redirectUri = null;
        $state       = null;

        $rawState = $request->query('state', '');
        $parts    = explode('.', $rawState, 2);
        if (count($parts) === 2) {
            [$payload, $sig] = $parts;
            $expected = hash_hmac('sha256', $payload, config('app.key'));
            if (hash_equals($expected, $sig)) {
                $decoded     = json_decode(base64_decode($payload), true);
                $clientId    = $decoded['client_id'] ?? null;
                $redirectUri = $decoded['redirect_uri'] ?? null;
                $state       = $decoded['state'] ?? null;
            }
        }

        try {
            $social = Socialite::driver($provider)->stateless()->user();
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
