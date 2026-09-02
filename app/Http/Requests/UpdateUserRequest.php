<?php

namespace App\Http\Requests;

use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('user');

        return [
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'email'       => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password'    => ['sometimes', 'nullable', 'string', Password::defaults()],
            'employee_id' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('users', 'employee_id')->ignore($userId)],
            'designation' => ['sometimes', 'required', 'string', 'max:255'],
            'role'        => ['sometimes', 'required', new Enum(UserType::class)],
        ];
    }
}
