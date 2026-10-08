<?php

namespace App\Http\Resources;

use App\Enums\RequisitionStep;
use App\Services\SettingsService;
use Illuminate\Http\Request;

/**
 * The signed-in user, plus the workflow steps they approve, so clients can show
 * approver-only UI without access to System Settings.
 */
class CurrentUserResource extends UserResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'approver_steps' => array_map(
                fn (RequisitionStep $step) => $step->value,
                app(SettingsService::class)->getStepsForUser($this->resource),
            ),
        ];
    }
}
