<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SensorSyncRequest extends FormRequest
{
    public function authorize()
    {
        return true; 
    }

    public function rules()
{
    return [
        'device_uid' => ['required', 'string', 'exists:devices,device_uid'],
        'timestamp' => ['required', 'integer', 'min:0'],

        'vitals' => ['required', 'array'], 
        'vitals.heart_rate' => ['required', 'integer', 'between:30,200'], 
        'vitals.spo2' => ['required', 'integer', 'between:70,100'], 
        'vitals.body_temperature' => ['required', 'numeric', 'between:35.0,42.0'],
        'vitals.ecg_signal' => ['required', 'numeric'],
        'vitals.hrv_rmssd' => 'required|numeric',
        'vitals.systolic_bp' => 'required|numeric',
        'vitals.diastolic_bp' => 'required|numeric',
        'vitals.bp_category_code' => 'required|integer|in:4,9,19',

        'movement' => ['required', 'array', 'size:10'], 
        'movement.*.x' => ['required', 'array', 'size:20'], 
        'movement.*.x.*' => ['required', 'numeric'], 
        'movement.*.y' => ['required', 'array', 'size:20'],
        'movement.*.y.*' => ['required', 'numeric'],
        'movement.*.z' => ['required', 'array', 'size:20'],
        'movement.*.z.*' => ['required', 'numeric'],

        'environment' => ['required', 'array'],
        'environment.uv_index' => ['required', 'numeric'],
    ];
}
}