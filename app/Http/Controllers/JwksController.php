<?php

namespace App\Http\Controllers;

use App\Services\JwksService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JwksController extends Controller
{
    public function __construct(
        protected JwksService $jwks,
    ) {}
    public function __invoke(): JsonResponse
    {
        return response()->json(
            $this->jwks->generate()
        );
    }
}
