<?php

namespace Tests\Feature;

use App\Enums\DecisionStatus;
use App\Enums\RequisitionStatus;
use App\Enums\RequisitionStep;
use App\Enums\UserType;
use App\Models\ApprovalStep;
use App\Models\Requisition;
use App\Models\SystemSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequisitionApprovalFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $approver;

    protected User $employee;

    protected Requisition $approvedByMe;

    protected Requisition $deniedByMe;

    protected Requisition $ownNotActed;

    protected function setUp(): void
    {
        parent::setUp();

        $this->approver = User::factory()->create(['role' => UserType::EMPLOYEE]);
        $this->employee = User::factory()->create(['role' => UserType::EMPLOYEE]);

        SystemSettings::create([
            'first_approver_user_id' => $this->approver->id,
            'updated_by_user_id' => $this->approver->id,
        ]);

        $this->approvedByMe = $this->makeRequisition('REQ-2026-0001', $this->employee, RequisitionStatus::PENDING, RequisitionStep::APPROVER_2);
        $this->act($this->approvedByMe, DecisionStatus::APPROVED);

        $this->deniedByMe = $this->makeRequisition('REQ-2026-0002', $this->employee, RequisitionStatus::DENIED, null);
        $this->act($this->deniedByMe, DecisionStatus::DENIED);

        $this->ownNotActed = $this->makeRequisition('REQ-2026-0003', $this->approver, RequisitionStatus::PENDING, RequisitionStep::APPROVER_2);
    }

    protected function makeRequisition(string $number, User $submitter, RequisitionStatus $status, ?RequisitionStep $step): Requisition
    {
        return Requisition::create([
            'requisition_number' => $number,
            'submitted_by_user_id' => $submitter->id,
            'current_step' => $step,
            'status' => $status,
            'total_expected_price' => 100.00,
        ]);
    }

    protected function act(Requisition $requisition, DecisionStatus $decision): void
    {
        ApprovalStep::create([
            'requisition_id' => $requisition->id,
            'step_type' => RequisitionStep::APPROVER_1,
            'acted_by_user_id' => $this->approver->id,
            'decision' => $decision,
            'acted_at' => now(),
        ]);
    }

    public function test_approval_mine_returns_only_requisitions_the_user_acted_on(): void
    {
        $ids = $this->actingAs($this->approver)
            ->getJson('/api/requisitions?approval=mine')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->json('data.*.id');

        $this->assertEqualsCanonicalizing([$this->approvedByMe->id, $this->deniedByMe->id], $ids);
    }

    public function test_without_approval_param_user_still_sees_own_and_acted_requisitions(): void
    {
        $this->actingAs($this->approver)
            ->getJson('/api/requisitions')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_submitted_mine_returns_only_requisitions_the_user_submitted(): void
    {
        $this->actingAs($this->approver)
            ->getJson('/api/requisitions?submitted=mine')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->ownNotActed->id);

        $ids = $this->actingAs($this->employee)
            ->getJson('/api/requisitions?submitted=mine')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->json('data.*.id');

        $this->assertEqualsCanonicalizing([$this->approvedByMe->id, $this->deniedByMe->id], $ids);
    }

    public function test_approval_mine_combines_with_status_filter(): void
    {
        $this->actingAs($this->approver)
            ->getJson('/api/requisitions?approval=mine&status=DENIED')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->deniedByMe->id);
    }
}
