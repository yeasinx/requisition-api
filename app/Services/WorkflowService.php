<?php

namespace App\Services;

use App\Enums\RequisitionStep;
use App\Enums\UserType;
use App\Models\User;

class WorkflowService
{
    public function __construct(protected SettingsService $settingsService)
    {}

    public function getInitialStep(User $submitter): RequisitionStep
    {
        $ceo = $this->settingsService->getSecondApprover();
        $pm = $this->settingsService->getFirstApprover();

        // 1. If the submitter is the CEO, skip App1 and App2, start at Business Controller
       if( $ceo && $submitter->is($ceo)) {
           return RequisitionStep::BUSINESS_CONTROLLER;
       }
        // 2. If the submitter is the PM, skip App1, start at App2
        if( $pm && $submitter->is($pm) ||
            $submitter->role === UserType::ACCOUNTS ||
            $submitter->role === UserType::HR_ADMIN
        ) {
            return RequisitionStep::APPROVER_2;
        }

        // 3. Default to the regular Employees: Start at PM (App1)
        return RequisitionStep::APPROVER_1;

    }

    public function getNextStep(RequisitionStep $currentStep): ?RequisitionStep
    {
        $sequence = [
            RequisitionStep::APPROVER_1,
            RequisitionStep::APPROVER_2,
            RequisitionStep::BUSINESS_CONTROLLER,
            RequisitionStep::ACCOUNTS,
            RequisitionStep::HR_ADMIN,
        ];

        $currentIndex = array_search($currentStep, $sequence);

        if($currentIndex === false || $currentIndex === count($sequence) - 1) {
            return null;
        }

        return $sequence[$currentIndex + 1];
    }
}
