<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePatientProfileRequest;
use App\Services\PatientProfileService;
use Illuminate\Http\JsonResponse;

class PatientProfileController extends Controller
{
    protected $profileService;

    public function __construct(PatientProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    public function updateMyProfile(UpdatePatientProfileRequest $request): JsonResponse
    {
        $patient = $request->user();

        $this->profileService->updateProfileByPatient($patient, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Your profile has been updated successfully.'
        ], 200);
    }
}