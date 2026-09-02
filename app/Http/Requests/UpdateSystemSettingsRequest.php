<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_approver_user_id'       => ['nullable', 'integer', 'exists:users,id'],
            'second_approver_user_id'      => ['nullable', 'integer', 'exists:users,id'],
            'business_controller_user_id'  => ['nullable', 'integer', 'exists:users,id'],
            'accounts_approver_user_id'    => ['nullable', 'integer', 'exists:users,id'],
            'hr_admin_approver_user_id'    => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
