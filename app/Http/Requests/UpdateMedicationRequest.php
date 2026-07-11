<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'dosage' => 'sometimes|string|max:100',
            'frequency' => 'sometimes|integer|min:1',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'condition_id' => 'nullable|exists:conditions,id',
            'stop_reason' => 'nullable|string|max:500|required_with:end_date', 
            'scheduled_times' => 'sometimes|array',
            'scheduled_times.*' => 'date_format:H:i',
        ];
    }
}