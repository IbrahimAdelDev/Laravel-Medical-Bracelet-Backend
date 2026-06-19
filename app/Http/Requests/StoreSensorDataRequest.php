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
        'timestamp' => ['required', 'integer', 'min:0'],
        'vitals' => ['required', 'array'], // بيانات الحيوية (مثل ضربات القلب، الأكسجين، إلخ)

        'vitals.heart_rate' => ['required', 'integer', 'between:30,200'], // مثال لقلب
        'vitals.spo2' => ['required', 'integer', 'between:70,100'], // مثال للأكسجين
        'vitals.body_temperature' => ['required', 'numeric', 'between:35.0,42.0'], // مثال للحرارة
        'vitals.ecg_signal' => ['required', 'numeric'],

        'movement' => ['required', 'array', 'size:10'], // لازم يكونوا 10 عناصر (10 ثواني)
        // فحص كل ثانية من الـ 10
        'movement.*.x' => ['required', 'array', 'size:20'], // كل مصفوفة x فيها 20 قراءة
        'movement.*.y' => ['required', 'array', 'size:20'],
        'movement.*.z' => ['required', 'array', 'size:20'],

        'environment' => ['required', 'array'],
        'environment.uv_index' => ['required', 'numeric'],

        'alerts' => ['required', 'array'],

        'alerts.sos_pressed' => ['required', 'boolean'],
        'alerts.emergency' => ['required', 'boolean'],
    ];
}
}