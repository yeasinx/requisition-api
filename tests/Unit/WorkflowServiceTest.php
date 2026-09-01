<?php

namespace Tests\Unit;

use App\Enums\RequisitionStep;
use App\Enums\UserType;
use App\Models\User;
use App\Services\SettingsService;
use App\Services\WorkflowService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class WorkflowServiceTest extends TestCase
{
    protected SettingsService|MockInterface $settingsService;
    protected WorkflowService $workflowService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settingsService = Mockery::mock(SettingsService::class);
        $this->workflowService = new WorkflowService($this->settingsService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_accounts_user_starts_at_approver_2(): void
    {
        $this->settingsService
            ->shouldReceive('getSecondApprover')
            ->once()
            ->andReturn(null);

        $this->settingsService
            ->shouldReceive('getFirstApprover')
            ->once()
            ->andReturn(null);

        $submitter = new User(['role' => UserType::ACCOUNTS]);
        $submitter->id = 10;

        $initialStep = $this->workflowService->getInitialStep($submitter);

        $this->assertSame(RequisitionStep::APPROVER_2, $initialStep);
    }

    public function test_hr_admin_user_starts_at_approver_2(): void
    {
        $this->settingsService
            ->shouldReceive('getSecondApprover')
            ->once()
            ->andReturn(null);

        $this->settingsService
            ->shouldReceive('getFirstApprover')
            ->once()
            ->andReturn(null);

        $submitter = new User(['role' => UserType::HR_ADMIN]);
        $submitter->id = 11;

        $initialStep = $this->workflowService->getInitialStep($submitter);

        $this->assertSame(RequisitionStep::APPROVER_2, $initialStep);
    }

    public function test_ceo_submitter_starts_at_business_controller(): void
    {
        $ceo = new User();
        $ceo->id = 1;

        $this->settingsService
            ->shouldReceive('getSecondApprover')
            ->once()
            ->andReturn($ceo);

        $this->settingsService
            ->shouldReceive('getFirstApprover')
            ->once()
            ->andReturn(null);

        $submitter = new User(['role' => UserType::EMPLOYEE]);
        $submitter->id = 1;

        $initialStep = $this->workflowService->getInitialStep($submitter);

        $this->assertSame(RequisitionStep::BUSINESS_CONTROLLER, $initialStep);
    }

    public function test_pm_submitter_starts_at_approver_2(): void
    {
        $pm = new User();
        $pm->id = 2;

        $this->settingsService
            ->shouldReceive('getSecondApprover')
            ->once()
            ->andReturn(null);

        $this->settingsService
            ->shouldReceive('getFirstApprover')
            ->once()
            ->andReturn($pm);

        $submitter = new User(['role' => UserType::EMPLOYEE]);
        $submitter->id = 2;

        $initialStep = $this->workflowService->getInitialStep($submitter);

        $this->assertSame(RequisitionStep::APPROVER_2, $initialStep);
    }

    public function test_regular_employee_starts_at_approver_1(): void
    {
        $ceo = new User();
        $ceo->id = 1;

        $pm = new User();
        $pm->id = 2;

        $this->settingsService
            ->shouldReceive('getSecondApprover')
            ->once()
            ->andReturn($ceo);

        $this->settingsService
            ->shouldReceive('getFirstApprover')
            ->once()
            ->andReturn($pm);

        $submitter = new User(['role' => UserType::EMPLOYEE]);
        $submitter->id = 99;

        $initialStep = $this->workflowService->getInitialStep($submitter);

        $this->assertSame(RequisitionStep::APPROVER_1, $initialStep);
    }

    public function test_get_next_step_advances_through_workflow_sequence(): void
    {
        $this->assertSame(
            RequisitionStep::APPROVER_2,
            $this->workflowService->getNextStep(RequisitionStep::APPROVER_1)
        );

        $this->assertSame(
            RequisitionStep::BUSINESS_CONTROLLER,
            $this->workflowService->getNextStep(RequisitionStep::APPROVER_2)
        );

        $this->assertSame(
            RequisitionStep::ACCOUNTS,
            $this->workflowService->getNextStep(RequisitionStep::BUSINESS_CONTROLLER)
        );

        $this->assertSame(
            RequisitionStep::HR_ADMIN,
            $this->workflowService->getNextStep(RequisitionStep::ACCOUNTS)
        );

        $this->assertNull(
            $this->workflowService->getNextStep(RequisitionStep::HR_ADMIN)
        );
    }
}
