<?php

namespace Tests\Feature;

use App\Enums\RequisitionStatus;
use App\Enums\RequisitionStep;
use App\Enums\UserType;
use App\Mail\RequisitionApprovedMail;
use App\Mail\RequisitionDeniedMail;
use App\Mail\RequisitionPendingApprovalMail;
use App\Models\Requisition;
use App\Models\SystemSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RequisitionWorkflowNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $employee;

    protected User $approver1;

    protected User $approver2;

    protected User $hrAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Intercept all mail sending
        Mail::fake();

        // 2. Create users for the flow
        $this->employee = User::factory()->create([
            'role' => UserType::EMPLOYEE,
        ]);

        $this->approver1 = User::factory()->create([
            'role' => UserType::EMPLOYEE,
        ]);

        $this->approver2 = User::factory()->create([
            'role' => UserType::EMPLOYEE,
        ]);

        $this->hrAdmin = User::factory()->create([
            'role' => UserType::HR_ADMIN,
        ]);

        // 3. Configure approver settings
        SystemSettings::create([
            'first_approver_user_id' => $this->approver1->id,
            'second_approver_user_id' => $this->approver2->id,
            'hr_admin_approver_user_id' => $this->hrAdmin->id,
            'updated_by_user_id' => $this->employee->id,
        ]);
    }

    /**
     * Flow 1: When an employee submits a requisition, the 1st approver gets a mail.
     */
    public function test_submitting_requisition_queues_email_to_first_approver(): void
    {
        $payload = [
            'items' => [
                [
                    'item_name' => 'MacBook Pro',
                    'description' => 'M3 Max 32GB',
                    'quantity' => 1,
                    'unit_price' => 2499.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->employee)
            ->postJson('/api/requisitions', $payload);

        $response->assertCreated();

        // Check that Approver 1 received the pending approval email
        Mail::assertQueued(RequisitionPendingApprovalMail::class, function ($mail) {
            return $mail->hasTo($this->approver1->email);
        });
    }

    /**
     * Flow 2: When Approver 1 approves, the submitter gets notified and Approver 2 gets notified.
     */
    public function test_approver_1_approving_notifies_submitter_and_next_approver(): void
    {
        $requisition = Requisition::create([
            'requisition_number' => 'REQ-2026-0001',
            'submitted_by_user_id' => $this->employee->id,
            'current_step' => RequisitionStep::APPROVER_1,
            'status' => RequisitionStatus::PENDING,
            'total_expected_price' => 2499.00,
        ]);

        $response = $this->actingAs($this->approver1)
            ->postJson("/api/requisitions/{$requisition->id}/approve", [
                'remarks' => 'Approved by Team Lead',
            ]);

        $response->assertOk();

        // 1. Submitter gets notified of step approval
        Mail::assertQueued(RequisitionApprovedMail::class, function ($mail) {
            return $mail->hasTo($this->employee->email)
                && ! $mail->isFullyApproved
                && $mail->remarks === 'Approved by Team Lead';
        });

        // 2. Next approver (Approver 2) gets notified that it is awaiting their approval
        Mail::assertQueued(RequisitionPendingApprovalMail::class, function ($mail) {
            return $mail->hasTo($this->approver2->email);
        });
    }

    /**
     * Flow 3: Denying without a reason fails validation (reason is required).
     */
    public function test_denying_without_reason_fails_validation(): void
    {
        $requisition = Requisition::create([
            'requisition_number' => 'REQ-2026-0002',
            'submitted_by_user_id' => $this->employee->id,
            'current_step' => RequisitionStep::APPROVER_1,
            'status' => RequisitionStatus::PENDING,
            'total_expected_price' => 500.00,
        ]);

        $response = $this->actingAs($this->approver1)
            ->postJson("/api/requisitions/{$requisition->id}/deny", []);

        $response->assertUnprocessable(); // 422 Validation Error
        Mail::assertNothingQueued();
    }

    /**
     * Flow 4: Denying with a reason notifies the submitter with the reason.
     */
    public function test_denying_with_reason_notifies_submitter_and_halts_workflow(): void
    {
        $requisition = Requisition::create([
            'requisition_number' => 'REQ-2026-0003',
            'submitted_by_user_id' => $this->employee->id,
            'current_step' => RequisitionStep::APPROVER_1,
            'status' => RequisitionStatus::PENDING,
            'total_expected_price' => 500.00,
        ]);

        $response = $this->actingAs($this->approver1)
            ->postJson("/api/requisitions/{$requisition->id}/deny", [
                'reason' => 'Item is not eligible under company policy.',
            ]);

        $response->assertOk();

        // Verify status changed to DENIED
        $this->assertDatabaseHas('requisitions', [
            'id' => $requisition->id,
            'status' => RequisitionStatus::DENIED,
            'current_step' => null,
        ]);

        // Verify denial email was queued to submitter with reason
        Mail::assertQueued(RequisitionDeniedMail::class, function ($mail) {
            return $mail->hasTo($this->employee->email)
                && $mail->reason === 'Item is not eligible under company policy.';
        });
    }

    /**
     * Flow 5: Final step approval notifies submitter that requisition is fully approved.
     */
    public function test_final_approver_approving_notifies_submitter_as_fully_approved(): void
    {
        $requisition = Requisition::create([
            'requisition_number' => 'REQ-2026-0004',
            'submitted_by_user_id' => $this->employee->id,
            'current_step' => RequisitionStep::HR_ADMIN,
            'status' => RequisitionStatus::PENDING,
            'total_expected_price' => 500.00,
        ]);

        $response = $this->actingAs($this->hrAdmin)
            ->postJson("/api/requisitions/{$requisition->id}/approve", [
                'remarks' => 'Final HR sign-off',
            ]);

        $response->assertOk();

        // Submitter receives notification that requisition is fully approved
        Mail::assertQueued(RequisitionApprovedMail::class, function ($mail) {
            return $mail->hasTo($this->employee->email)
                && $mail->isFullyApproved === true;
        });

        // No more pending approval emails should be queued
        Mail::assertNotQueued(RequisitionPendingApprovalMail::class);
    }
}
