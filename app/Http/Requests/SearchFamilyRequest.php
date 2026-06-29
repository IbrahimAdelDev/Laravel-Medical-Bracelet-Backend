<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchFamilyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // الإيميل مطلوب، لازم يكون إيميل صحيح، وموجود في جدول users
            'email' => 'required|email|exists:users,email',
        ];
    }
}