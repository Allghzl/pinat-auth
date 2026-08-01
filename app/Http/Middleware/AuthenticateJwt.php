<?php

namespace App\Http\Middleware;

use App\Services\UserAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthenticateJwt
{
    public function __construct(
        protected UserAuthService $auth,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'message' => 'Missing bearer token.',
            ], 401);
        }

        try {
            $user = $this->auth->resolveUser($token);

            auth()->setUser($user);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 401);
        }

        return $next($request);
    }
}
