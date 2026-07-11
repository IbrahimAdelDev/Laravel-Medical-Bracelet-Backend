<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DiabetesCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'polyuria' => 'required|boolean',
            'polydipsia' => 'required|boolean',
            'sudden_weight_loss' => 'required|boolean',
            'weakness' => 'required|boolean',
            'polyphagia' => 'required|boolean',
            'genital_thrush' => 'required|boolean',
            'visual_blurring' => 'required|boolean',
            'itching' => 'required|boolean',
            'irritability' => 'required|boolean',
            'delayed_healing' => 'required|boolean',
            'partial_paresis' => 'required|boolean',
            'muscle_stiffness' => 'required|boolean',
            'alopecia' => 'required|boolean',
            'obesity' => 'required|boolean',
        ];
    }
}