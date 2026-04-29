<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuthService;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $data = $this->authService->login($request->only('email', 'password'));

        $result = [
            'status' => 'success',
            'message' => 'Login successful',
            'data' => $data
        ];

        $accessCookie = cookie('access_token', $data['access_token'], 15, null, null, env('APP_ENV') !== 'local', true); 
        
        $refreshCookie = cookie('refresh_token', $data['refresh_token'], 10080, null, null, env('APP_ENV') !== 'local', true);

        return response()->json($result)
            ->withCookie($accessCookie)
            ->withCookie($refreshCookie);
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        $forgetAccess = cookie()->forget('access_token');
        $forgetRefresh = cookie()->forget('refresh_token');

        return response()->json(['message' => 'Logged out successfully'])
            ->withCookie($forgetAccess)
            ->withCookie($forgetRefresh);
    }
}
