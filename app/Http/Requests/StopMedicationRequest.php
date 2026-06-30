<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StopMedicationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'stop_reason' => 'required|string|max:500',
        ];
    }
}