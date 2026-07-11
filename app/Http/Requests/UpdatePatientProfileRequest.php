<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'age' => 'sometimes|integer|min:1|max:120',
            'height' => 'sometimes|numeric|min:30|max:250',
            'weight' => 'sometimes|numeric|min:2|max:300',
        ];
    }
}