<?php

namespace App\Http\Requests;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
            'employee_id' => ['required', 'string', 'max:50', 'unique:users,employee_id'],
            'designation' => ['required', 'string', 'max:255'],
            'role' => ['required', ...self::roleRules($this->user())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return self::roleMessages();
    }

    /**
     * Valid roles; only a Super Admin may grant the Super Admin role.
     *
     * @return array<int, mixed>
     */
    public static function roleRules(?User $actor): array
    {
        $rules = [new Enum(UserType::class)];

        if ($actor?->role !== UserType::SUPER_ADMIN) {
            $rules[] = Rule::notIn([UserType::SUPER_ADMIN->value]);
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public static function roleMessages(): array
    {
        return [
            'role.not_in' => 'Only a Super Admin can grant the Super Admin role.',
        ];
    }
}
