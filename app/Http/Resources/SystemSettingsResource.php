<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemSettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $formatUser = fn ($user) => $user ? [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'employee_id' => $user->employee_id,
            'designation' => $user->designation,
        ] : null;

        return [
            'id' => $this->id,
            'first_approver' => $formatUser($this->firstApprover),
            'second_approver' => $formatUser($this->secondApprover),
            'business_controller' => $formatUser($this->businessController),
            'accounts_approver' => $formatUser($this->accountsApprover),
            'hr_admin_approver' => $formatUser($this->hrAdminApprover),
            'updated_by' => $formatUser($this->updatedBy),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
