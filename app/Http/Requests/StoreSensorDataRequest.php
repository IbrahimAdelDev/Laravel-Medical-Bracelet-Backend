<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSensorDataRequest extends FormRequest
{
    public function authorize()
    {
        // هنسمح بيه مؤقتاً لحد ما نعمل نظام حماية (API Key) للـ ESP32
        return true; 
    }

    public function rules()
{
    return [
        'device_uid' => ['required', 'string', 'exists:devices,device_uid'],
        'movement' => ['required', 'array', 'size:10'], // لازم يكونوا 10 عناصر (10 ثواني)
        
        // فحص كل ثانية من الـ 10
        'movement.*.x' => ['required', 'array', 'size:20'], // كل مصفوفة x فيها 20 قراءة
        'movement.*.y' => ['required', 'array', 'size:20'],
        'movement.*.z' => ['required', 'array', 'size:20'],
    ];
}
}