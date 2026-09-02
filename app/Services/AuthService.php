<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    protected User $userModel;

    public function __construct(?User $userModel = null)
    {
        $this->userModel = $userModel ?? new User;
    }

    /**
     * Authenticate user credentials and create an API token.
     *
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     */
    public function attemptLogin(string $email, string $password, string $tokenName = 'auth_token'): array
    {
        $user = $this->userModel->newQuery()->where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken($tokenName)->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Revoke the user's current access token.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
