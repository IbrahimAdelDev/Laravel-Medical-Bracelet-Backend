<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConditionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'disease_name' => 'required|string|max:255',
            'status' => 'required|in:active,resolved',
            'diagnosed_at' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}