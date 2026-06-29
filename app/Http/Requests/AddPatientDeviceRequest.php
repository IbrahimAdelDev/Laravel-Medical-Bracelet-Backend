<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddPatientDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // الـ uid مطلوب، نص، ولازم يكون مش متسجل لجهاز تاني قبل كده
            'device_uid' => 'required|string|unique:devices,device_uid',
            'status' => 'nullable|in:active,inactive'
        ];
    }
}