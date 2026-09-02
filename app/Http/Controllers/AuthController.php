<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    /**
     * Authenticate user and issue Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->authService->attemptLogin(
            $validated['email'],
            $validated['password']
        );

        return response()->json([
            'message'      => 'Login successful',
            'access_token' => $result['token'],
            'token_type'   => 'Bearer',
            'user'         => new UserResource($result['user']),
        ]);
    }

    /**
     * Log out current user and revoke current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Return authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        return (new UserResource($request->user()))->response();
    }
}
