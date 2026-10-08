<?php

namespace Tests\Feature;

use App\Enums\DecisionStatus;
use App\Enums\RequisitionStatus;
use App\Enums\RequisitionStep;
use App\Enums\UserType;
use App\Models\ApprovalStep;
use App\Models\Requisition;
use App\Models\RequisitionAttachment;
use App\Models\SystemSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeletedUserHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $submitter;

    protected User $approver;

    protected Requisition $requisition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['role' => UserType::SUPER_ADMIN]);
        $this->submitter = User::factory()->create(['role' => UserType::EMPLOYEE]);
        $this->approver = User::factory()->create(['role' => UserType::EMPLOYEE]);

        SystemSettings::create(['updated_by_user_id' => $this->superAdmin->id]);

        $this->requisition = Requisition::create([
            'requisition_number' => 'REQ-2026-0001',
            'submitted_by_user_id' => $this->submitter->id,
            'current_step' => RequisitionStep::APPROVER_2,
            'status' => RequisitionStatus::PENDING,
            'total_expected_price' => 100.00,
        ]);

        ApprovalStep::create([
            'requisition_id' => $this->requisition->id,
            'step_type' => RequisitionStep::APPROVER_1,
            'acted_by_user_id' => $this->approver->id,
            'decision' => DecisionStatus::APPROVED,
            'acted_at' => now(),
        ]);

        RequisitionAttachment::create([
            'requisition_id' => $this->requisition->id,
            'uploaded_by_user_id' => $this->submitter->id,
            'original_name' => 'quote.pdf',
            'path' => 'requisitions/1/quote.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        // Both accounts are soft-deleted after taking part in the requisition.
        $this->approver->delete();
        $this->submitter->delete();
    }

    public function test_listing_keeps_working_after_participants_are_deleted(): void
    {
        $this->actingAs($this->superAdmin)
            ->getJson('/api/requisitions')
            ->assertOk()
            ->assertJsonPath('data.0.approvals.0.acted_by.name', $this->approver->name)
            ->assertJsonPath('data.0.submitted_by.name', $this->submitter->name)
            ->assertJsonPath('data.0.attachments.0.uploaded_by.name', $this->submitter->name);
    }

    public function test_detail_keeps_the_audit_trail_after_participants_are_deleted(): void
    {
        $this->actingAs($this->superAdmin)
            ->getJson("/api/requisitions/{$this->requisition->id}")
            ->assertOk()
            ->assertJsonPath('data.approvals.0.acted_by.id', $this->approver->id)
            ->assertJsonPath('data.submitted_by.id', $this->submitter->id);
    }
}
