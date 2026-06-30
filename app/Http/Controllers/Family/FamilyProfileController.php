<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Http\Requests\ToggleSelfManageRequest;
use App\Http\Requests\UpdatePatientProfileRequest;
use App\Services\PatientProfileService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FamilyProfileController extends Controller
{
    protected PatientProfileService $profileService;

    public function __construct(PatientProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    public function toggleManage(ToggleSelfManageRequest $request, $patientId): JsonResponse
    {
        $familyId = $request->user()->id;
        $status = $request->validated()['can_self_manage'];

        $this->profileService->toggleSelfManageStatus($familyId, (int) $patientId, $status);

        return response()->json([
            'status' => 'success',
            'message' => "Patient self-management status has been updated to " . ($status ? 'true' : 'false') . "."
        ], 200);
    }

    public function updatePatientProfile(UpdatePatientProfileRequest $request, $patientId): JsonResponse
    {
        $familyId = $request->user()->id;

        $this->profileService->updateProfileByFamily($familyId, (int) $patientId, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Patient profile has been updated successfully.'
        ], 200);
    }
}