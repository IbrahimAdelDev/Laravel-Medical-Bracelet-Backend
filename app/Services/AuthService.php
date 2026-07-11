<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function login(array $credentials)
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return $this->generateTokens($user);
    }

    public function generateTokens(User $user)
    {
        $user->tokens()->delete(); 

        $accessToken = $user->createToken('access_token', ['access-api'], now()->addMinutes(60))->plainTextToken;
        $refreshToken = $user->createToken('refresh_token', ['issue-access-token'], now()->addDays(7))->plainTextToken;

        return [
            'user' => $user,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }

    public function logout(User $user)
    {
        // Delete only the current access token
        $user->currentAccessToken()?->delete();
        $user->tokens()->where('name', 'refresh_token')->delete();
        return true;
    }
}