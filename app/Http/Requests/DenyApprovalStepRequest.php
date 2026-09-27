<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DenyApprovalStepRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required_without:remarks', 'nullable', 'string', 'max:1000'],
            'remarks' => ['required_without:reason', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required_without' => 'A reason is required when denying a requisition.',
            'remarks.required_without' => 'A reason is required when denying a requisition.',
        ];
    }

    public function getReason(): string
    {
        return (string) ($this->input('reason') ?? $this->input('remarks'));
    }
}
