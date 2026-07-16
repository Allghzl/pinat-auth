<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ServiceAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceAuthController extends Controller
{
    public function __construct(
        protected ServiceAuthService $serviceAuthService
    ) {}

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'client_id' => 'required|uuid',
            'client_secret' => 'required|string'
        ]);

        return response()->json(
            $this->serviceAuthService->login(
                clientId: $request->client_id,
                clientSecret: $request->client_secret,
            )
        );
    }
}
