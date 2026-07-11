<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log;

class AutoRefreshTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    // public function handle(Request $request, Closure $next)
    // {
    //     $accessToken = $request->bearerToken();
    
    //     $user = Auth::guard('sanctum')->user();

    //     if ($user) {
    //         // for sanctum to work properly, we need to set the user on the authentication guard and the request
    //         Auth::setUser($user);

    //         $request->setUserResolver(function () use ($user) {
    //             return $user;
    //         });
            
    //         return $next($request);
    //     }

    //     $refreshToken = $request->header('X-Refresh-Token');

    //     if (!$refreshToken) {
    //         return response()->json(['message' => 'Unauthenticated.'], 401);
    //     }

    //     $token = PersonalAccessToken::findToken($refreshToken);

    //     if (!$token || $token->name !== 'refresh_token' || ($token->expires_at && $token->expires_at->isPast())) {
    //         return response()->json(['message' => 'Session expired. Please login again.'], 401);
    //     }

    //     $user = $token->tokenable;
    //     $newAccessToken = $user->createToken('access_token', ['access-api'], now()->addMinutes(60))->plainTextToken;
    //     $request->headers->set('Authorization', 'Bearer ' . $newAccessToken);

    //     Auth::setUser($user);
    //     $request->setUserResolver(function () use ($user) {
    //         return $user;
    //     });

    //     $response = $next($request);
        
    //     $request->headers->set('Authorization', 'Bearer ' . $newAccessToken);
        
    //     $response->headers->set('New-Access-Token', $newAccessToken);
    //     $response->headers->set('X-Refresh-Token', $refreshToken);

    //     return $response;
    // }
    public function handle(Request $request, Closure $next)
{
    if (Auth::guard('sanctum')->check()) {
        $user = Auth::guard('sanctum')->user();
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        return $next($request);
    }

    $refreshToken = $request->header('X-Refresh-Token');
    if (!$refreshToken) {
        return response()->json(['message' => 'Unauthenticated.'], 401); 
    }

    $token = PersonalAccessToken::findToken($refreshToken);
    if (!$token || $token->expires_at?->isPast()) {
        return response()->json(['message' => 'Session expired. Please login again.'], 401); 
    }

    $user = $token->tokenable;

    $currentAccessToken = $request->bearerToken();
        
    if ($currentAccessToken) {
        $personalAccessToken = PersonalAccessToken::findToken($currentAccessToken);
        if ($personalAccessToken) {
            $personalAccessToken->delete();
        }
    }

    $newAccessToken = $user->createToken('access_token', ['access-api'], now()->addMinutes(60))->plainTextToken;
    
    $request->headers->set('Authorization', 'Bearer ' . $newAccessToken);

    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    $response = $next($request);
    $response->headers->set('New-Access-Token', $newAccessToken);
    return $response;
}
}
