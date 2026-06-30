<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveAlertsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alert_ids'   => 'required|array|min:1',
            'alert_ids.*' => 'required|integer|exists:alerts,id',
        ];
    }

    public function messages(): array
    {
        return [
            'alert_ids.required' => 'Please provide at least one alert ID to resolve.',
            'alert_ids.array'    => 'The alert IDs must be sent as an array.',
        ];
    }
}