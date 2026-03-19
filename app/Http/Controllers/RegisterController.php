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

    public function registerNormal(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        $user = $this->userService->registerNormalUser($validated);
        return response()->json($this->authService->generateTokens($user));
    }

    public function registerDoctor(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        $doctor = $this->userService->registerDoctor($validated);
        return response()->json($this->authService->generateTokens($doctor));
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
