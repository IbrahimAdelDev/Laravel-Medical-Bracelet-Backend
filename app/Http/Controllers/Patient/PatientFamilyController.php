<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Services\FamilyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\SearchFamilyRequest;
use App\Services\PatientRelationService;

class PatientFamilyController extends Controller
{
    protected FamilyService $familyService;
    protected PatientRelationService $relationService;

    public function __construct(FamilyService $familyService, PatientRelationService $relationService)
    {
        $this->familyService = $familyService;
        $this->relationService = $relationService;
    }

    /**
     * عرض أفراد العائلة المتاحين للربط
     */
    public function searchByEmail(SearchFamilyRequest $request): JsonResponse
    {
        $patientId = $request->user()->id;
        
        $family = $this->familyService->findFamilyByEmail($patientId, $request->email);

        if (!$family) {
            return response()->json([
                'status' => 'error',
                'message' => 'No available family member found with this email, or they are already linked to you.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $family
        ], 200);
    }

    /**
     * ربط فرد العائلة بالحساب
     */
    public function addFamilyMember(Request $request, $familyId): JsonResponse
    {
        $patientId = $request->user()->id;

        if($patientId == $familyId) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot link yourself as a family member.'
            ], 400);
        }

        // تفادينا الـ TypeError اللي ظهرتلك وعملنا Cast هنا فوراً
        $this->familyService->attachFamilyToPatient($patientId, (int) $familyId);

        return response()->json([
            'status' => 'success',
            'message' => 'Family member has been successfully linked to your profile.'
        ], 200);
    }

    public function unlinkFamily(Request $request, $familyId): JsonResponse
    {
        $patientId = $request->user()->id;
        
        $this->relationService->unlinkFamilyFromPatient((int) $patientId, (int) $familyId);

        return response()->json([
            'status' => 'success',
            'message' => 'Family member has been successfully removed from your monitoring list.'
        ], 200);
    }
}