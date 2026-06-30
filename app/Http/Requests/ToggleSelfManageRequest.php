<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ToggleSelfManageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // الإجبار على إرسال true أو false أو 1 أو 0
            'can_self_manage' => 'required|boolean', 
        ];
    }
}