<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSystemSettingsRequest;
use App\Http\Resources\SystemSettingsResource;
use App\Models\SystemSettings;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class SystemSettingsController extends Controller
{
    public function __construct(protected SettingsService $settingsService) {}

    /**
     * Display current system settings and assigned approvers.
     */
    public function show(): JsonResponse
    {
        Gate::authorize('view', SystemSettings::class);

        $settings = $this->settingsService->getSettings()
            ->load(['firstApprover', 'secondApprover', 'businessController', 'accountsApprover', 'hrAdminApprover', 'updatedBy']);

        return (new SystemSettingsResource($settings))->response();
    }

    /**
     * Update designated approvers.
     */
    public function update(UpdateSystemSettingsRequest $request): JsonResponse
    {
        Gate::authorize('update', SystemSettings::class);

        $settings = $this->settingsService->updateSettings(
            $request->validated(),
            $request->user()->id
        );

        $settings->load(['firstApprover', 'secondApprover', 'businessController', 'accountsApprover', 'hrAdminApprover', 'updatedBy']);

        return (new SystemSettingsResource($settings))->response();
    }
}
