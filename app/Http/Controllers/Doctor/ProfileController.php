<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Services\DoctorProfileService;

class ProfileController extends Controller
{
    public function __construct(private readonly DoctorProfileService $profileService) {}

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'phones' => 'sometimes|array',
            'phones.*.phone_number' => 'required_with:phones|string|max:20',
            'phones.*.type' => 'nullable|string|max:50',
        ]);

        try {
            $updatedUser = $this->profileService->updateProfile($user, $validated);

            return response()->json([
                'status' => 'success', 
                'message' => 'Profile updated successfully.', 
                'data' => $updatedUser
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء تحديث البيانات الطبيب.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // public function updatePassword(Request $request): JsonResponse
    // {
    //     $validated = $request->validate([
    //         'current_password' => 'required|current_password',
    //         'new_password' => 'required|min:8|confirmed',
    //     ]);

    //     $request->user()->update([
    //         'password' => Hash::make($validated['new_password']),
    //     ]);

    //     return response()->json(['status' => 'success', 'message' => 'Password changed successfully.']);
    // }
}