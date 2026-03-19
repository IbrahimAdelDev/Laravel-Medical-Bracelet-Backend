<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AutoRefreshTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // if the access token is valid, just proceed
        if (Auth::guard('sanctum')->check()) {
            return $next($request);
        }

        // if the access token is expired, check the refresh token
        $refreshToken = $request->header('X-Refresh-Token');

        if (!$refreshToken) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = PersonalAccessToken::findToken($refreshToken);

        // if the refresh token is invalid or expired, ask the user to login again
        if (!$token || $token->name !== 'refresh_token' || $token->expires_at->isPast()) {
            return response()->json(['message' => 'Session expired. Please login again.'], 401);
        }

        // if the refresh token is valid, create a new access token
        $user = $token->tokenable;
        $newAccessToken = $user->createToken('access_token', ['*'], now()->addMinutes(15))->plainTextToken;

        // set the user on the authentication guard
        Auth::setUser($user);

        // continue the request, and add the new token to the Headers so the frontend can use it
        $response = $next($request);
        $response->headers->set('New-Access-Token', $newAccessToken);

        return $response;
    }
}
