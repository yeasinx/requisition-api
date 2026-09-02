<?php

namespace Tests\Unit;

use App\Enums\DecisionStatus;
use App\Enums\RequisitionStatus;
use App\Enums\RequisitionStep;
use App\Enums\UserType;
use App\Models\ApprovalStep;
use App\Models\Requisition;
use App\Models\User;
use App\Services\SettingsService;
use App\Services\WorkflowService;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

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

    public function test_approve_advances_to_next_step_when_not_final_step(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn($callback) => $callback());

        $approver = new User();
        $approver->id = 5;

        $requisition = Mockery::mock(Requisition::class)->makePartial();
        $requisition->id = 100;
        $requisition->current_step = RequisitionStep::APPROVER_1;
        $requisition->status = RequisitionStatus::PENDING;

        $approvalMock = Mockery::mock('alias:' . ApprovalStep::class);
        $approvalMock->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) use ($requisition, $approver) {
                return $data['requisition_id'] === $requisition->id
                    && $data['step_type'] === RequisitionStep::APPROVER_1
                    && $data['acted_by_user_id'] === $approver->id
                    && $data['decision'] === DecisionStatus::APPROVED
                    && $data['remarks'] === 'Looks good';
            }))
            ->andReturn(new ApprovalStep());

        $requisition->shouldReceive('update')
            ->once()
            ->with(['current_step' => RequisitionStep::APPROVER_2])
            ->andReturn(true);

        $requisition->shouldReceive('load')
            ->once()
            ->with(['submittedBy', 'items', 'approvals.actedBy'])
            ->andReturnSelf();

        $result = $this->workflowService->approve($requisition, $approver, 'Looks good');

        $this->assertSame($requisition, $result);
    }

    public function test_approve_marks_status_approved_when_final_step_reached(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn($callback) => $callback());

        $approver = new User();
        $approver->id = 6;

        $requisition = Mockery::mock(Requisition::class)->makePartial();
        $requisition->id = 101;
        $requisition->current_step = RequisitionStep::HR_ADMIN;
        $requisition->status = RequisitionStatus::PENDING;

        $approvalMock = Mockery::mock('alias:' . ApprovalStep::class);
        $approvalMock->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) use ($requisition, $approver) {
                return $data['requisition_id'] === $requisition->id
                    && $data['step_type'] === RequisitionStep::HR_ADMIN
                    && $data['acted_by_user_id'] === $approver->id
                    && $data['decision'] === DecisionStatus::APPROVED
                    && $data['remarks'] === null;
            }))
            ->andReturn(new ApprovalStep());

        $requisition->shouldReceive('update')
            ->once()
            ->with([
                'status'       => RequisitionStatus::APPROVED,
                'current_step' => null,
            ])
            ->andReturn(true);

        $requisition->shouldReceive('load')
            ->once()
            ->with(['submittedBy', 'items', 'approvals.actedBy'])
            ->andReturnSelf();

        $result = $this->workflowService->approve($requisition, $approver);

        $this->assertSame($requisition, $result);
    }

    public function test_deny_halts_workflow_and_sets_status_denied(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn($callback) => $callback());

        $approver = new User();
        $approver->id = 7;

        $requisition = Mockery::mock(Requisition::class)->makePartial();
        $requisition->id = 102;
        $requisition->current_step = RequisitionStep::BUSINESS_CONTROLLER;
        $requisition->status = RequisitionStatus::PENDING;

        $approvalMock = Mockery::mock('alias:' . ApprovalStep::class);
        $approvalMock->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) use ($requisition, $approver) {
                return $data['requisition_id'] === $requisition->id
                    && $data['step_type'] === RequisitionStep::BUSINESS_CONTROLLER
                    && $data['acted_by_user_id'] === $approver->id
                    && $data['decision'] === DecisionStatus::DENIED
                    && $data['remarks'] === 'Budget exceeded';
            }))
            ->andReturn(new ApprovalStep());

        $requisition->shouldReceive('update')
            ->once()
            ->with([
                'status'       => RequisitionStatus::DENIED,
                'current_step' => null,
            ])
            ->andReturn(true);

        $requisition->shouldReceive('load')
            ->once()
            ->with(['submittedBy', 'items', 'approvals.actedBy'])
            ->andReturnSelf();

        $result = $this->workflowService->deny($requisition, $approver, 'Budget exceeded');

        $this->assertSame($requisition, $result);
    }
}
