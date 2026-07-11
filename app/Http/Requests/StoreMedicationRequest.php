<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'dosage' => 'required|string|max:100', 
            'frequency' => 'required|integer|min:1', 
            'start_date' => 'required|date|after_or_equal:today',
            'condition_id' => 'nullable|exists:conditions,id',
            'stop_reason' => 'nullable|string|max:500|required_with:end_date', 
            'scheduled_times' => 'required|array',
            'scheduled_times.*' => 'date_format:H:i',
        ];
    }
}