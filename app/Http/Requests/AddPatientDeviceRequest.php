<?php

namespace App\Http\Requests;
use Illuminate\Support\Facades\DB;

use Illuminate\Foundation\Http\FormRequest;

class AddPatientDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $patientId = $this->route('id');

        // استعلام سريع جداً لمعرفة هل يمتلك المريض جهاز مسبقاً
        $hasDevice = DB::table('devices')->where('patient_id', $patientId)->exists();

        return [
            // لو عنده جهاز -> اختياري. لو معندوش -> إجباري.
            'device_uid' => [
                $hasDevice ? 'nullable' : 'required',
                'string',
                'unique:devices,device_uid'
            ],
            'status' => 'nullable|in:active,inactive'
        ];
    }
    
    // رسالة مخصصة عشان لو نسي يبعته والمريض معندوش جهاز
    public function messages(): array
    {
        return [
            'device_uid.required' => 'This patient does not have a registered medical device. Please provide a device UID.'
        ];
    }
}