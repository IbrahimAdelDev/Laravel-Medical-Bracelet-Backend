<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Services\UserService;

class RegisterController extends Controller
{
    protected $userService;
    protected $authService;
    // protected clone $userService;
    // protected clone $authService;

    public function __construct(UserService $userService, AuthService $authService)
    {
        $this->userService = $userService;
        $this->authService = $authService;
    }

    private function respondWithTokens($data)
    {
        $accessCookie = cookie('access_token', $data['access_token'], 15, null, null, env('APP_ENV') !== 'local', true); 
        $refreshCookie = cookie('refresh_token', $data['refresh_token'], 10080, null, null, env('APP_ENV') !== 'local', true);

        $result = [
            'status' => 'success',
            'message' => 'Registration successful',
            'data' => $data
        ];
        return response()->json($result)
                         ->withCookie($accessCookie)
                         ->withCookie($refreshCookie);
    }

    public function registerNormal(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        $user = $this->userService->registerNormalUser($validated);

        $tokenData = $this->authService->generateTokens($user);
        
        return $this->respondWithTokens($tokenData);
    }

    public function registerDoctor(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        $doctor = $this->userService->registerDoctor($validated);

        $tokenData = $this->authService->generateTokens($doctor);

        return $this->respondWithTokens($tokenData);
    }

    public function registerSecretary(Request $request)
    {
        $validated = $request->validate([
            'secretary_id' => 'nullable|exists:users,id',
            'name' => 'required_without:secretary_id|string|max:255',
            'email' => 'required_without:secretary_id|email|unique:users',
            'password' => 'required_without:secretary_id|min:6'
        ]);

        $secretary = $this->userService->assignOrCreateSecretary($validated, $request->user());
        return response()->json([
            'message' => 'Secretary assigned successfully',
            'secretary' => $secretary
        ]);
    }
}
