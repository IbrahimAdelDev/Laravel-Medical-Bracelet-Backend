<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MedicationHistoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'dosage' => 'required|string|max:255', 
            'start_date' => 'required|date|before_or_equal:today',
            'condition_id' => 'nullable|exists:conditions,id', 
        ];
    }
}