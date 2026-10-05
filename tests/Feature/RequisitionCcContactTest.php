<?php

namespace Tests\Feature;

use App\Enums\RequisitionStatus;
use App\Enums\RequisitionStep;
use App\Enums\UserType;
use App\Mail\RequisitionCcMail;
use App\Models\CcContact;
use App\Models\Requisition;
use App\Models\SystemSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RequisitionCcContactTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $employee;

    protected User $approver1;

    protected User $hrAdmin;

    protected CcContact $headOfOps;

    protected CcContact $auditor;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->superAdmin = User::factory()->create(['role' => UserType::SUPER_ADMIN]);
        $this->employee = User::factory()->create(['role' => UserType::EMPLOYEE]);
        $this->approver1 = User::factory()->create(['role' => UserType::EMPLOYEE]);
        $this->hrAdmin = User::factory()->create(['role' => UserType::HR_ADMIN]);

        SystemSettings::create([
            'first_approver_user_id' => $this->approver1->id,
            'hr_admin_approver_user_id' => $this->hrAdmin->id,
            'updated_by_user_id' => $this->superAdmin->id,
        ]);

        $this->headOfOps = CcContact::create(['name' => 'Head of Ops', 'designation' => 'Director', 'email' => 'ops@example.com']);
        $this->auditor = CcContact::create(['name' => 'Auditor', 'designation' => null, 'email' => 'audit@example.com']);
    }

    protected function payload(array $extra = []): array
    {
        return [
            'items' => [
                ['item_name' => 'Laptop', 'description' => 'Dev machine', 'quantity' => 1, 'unit_price' => 1000],
            ],
            ...$extra,
        ];
    }

    protected function makeRequisition(RequisitionStep $step, array $ccContacts = []): Requisition
    {
        $requisition = Requisition::create([
            'requisition_number' => 'REQ-2026-0001',
            'submitted_by_user_id' => $this->employee->id,
            'current_step' => $step,
            'status' => RequisitionStatus::PENDING,
            'total_expected_price' => 100.00,
        ]);

        $requisition->ccContacts()->sync(array_map(fn ($c) => $c->id, $ccContacts));

        return $requisition;
    }

    public function test_super_admin_manages_contacts_and_others_can_only_list(): void
    {
        $id = $this->actingAs($this->superAdmin)
            ->postJson('/api/cc-contacts', ['name' => 'CFO', 'designation' => 'Finance', 'email' => 'cfo@example.com'])
            ->assertCreated()
            ->assertJsonPath('data.email', 'cfo@example.com')
            ->json('data.id');

        $this->actingAs($this->superAdmin)
            ->putJson("/api/cc-contacts/{$id}", ['designation' => 'Chief Financial Officer'])
            ->assertOk()
            ->assertJsonPath('data.designation', 'Chief Financial Officer');

        $this->actingAs($this->employee)
            ->getJson('/api/cc-contacts')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->actingAs($this->employee)
            ->postJson('/api/cc-contacts', ['name' => 'X', 'email' => 'x@example.com'])
            ->assertForbidden();
        $this->actingAs($this->employee)->deleteJson("/api/cc-contacts/{$id}")->assertForbidden();

        $this->actingAs($this->superAdmin)->deleteJson("/api/cc-contacts/{$id}")->assertOk();
        $this->assertSoftDeleted('cc_contacts', ['id' => $id]);
    }

    public function test_contact_email_must_be_unique_among_active_contacts(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('/api/cc-contacts', ['name' => 'Dup', 'email' => 'ops@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        // A deleted contact's email can be reused
        $this->auditor->delete();
        $this->actingAs($this->superAdmin)
            ->postJson('/api/cc-contacts', ['name' => 'New Auditor', 'email' => 'audit@example.com'])
            ->assertCreated();
    }

    public function test_submitting_with_cc_contacts_links_them_and_emails_each_one(): void
    {
        $this->actingAs($this->employee)
            ->postJson('/api/requisitions', $this->payload(['cc_contact_ids' => [$this->headOfOps->id, $this->auditor->id]]))
            ->assertCreated()
            ->assertJsonCount(2, 'data.cc_contacts');

        $this->assertDatabaseCount('cc_contact_requisition', 2);

        Mail::assertQueued(RequisitionCcMail::class, 2);
        Mail::assertQueued(RequisitionCcMail::class, fn ($mail) => $mail->hasTo('ops@example.com')
            && $mail->event === RequisitionCcMail::SUBMITTED);
    }

    public function test_cc_is_optional_and_rejects_unknown_or_deleted_contacts(): void
    {
        $this->actingAs($this->employee)
            ->postJson('/api/requisitions', $this->payload())
            ->assertCreated()
            ->assertJsonCount(0, 'data.cc_contacts');

        Mail::assertNotQueued(RequisitionCcMail::class);

        $this->auditor->delete();

        $this->actingAs($this->employee)
            ->postJson('/api/requisitions', $this->payload(['cc_contact_ids' => [$this->auditor->id]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cc_contact_ids.0');

        $this->actingAs($this->employee)
            ->postJson('/api/requisitions', $this->payload(['cc_contact_ids' => [99999]]))
            ->assertUnprocessable();
    }

    public function test_cc_contacts_are_emailed_only_on_final_approval(): void
    {
        $requisition = $this->makeRequisition(RequisitionStep::APPROVER_1, [$this->headOfOps]);

        $this->actingAs($this->approver1)
            ->postJson("/api/requisitions/{$requisition->id}/approve")
            ->assertOk();

        Mail::assertNotQueued(RequisitionCcMail::class);

        $requisition->update(['current_step' => RequisitionStep::HR_ADMIN]);

        $this->actingAs($this->hrAdmin)
            ->postJson("/api/requisitions/{$requisition->id}/approve")
            ->assertOk();

        Mail::assertQueued(RequisitionCcMail::class, fn ($mail) => $mail->hasTo('ops@example.com')
            && $mail->event === RequisitionCcMail::APPROVED);
    }

    public function test_cc_contacts_are_emailed_on_denial_with_reason(): void
    {
        $requisition = $this->makeRequisition(RequisitionStep::APPROVER_1, [$this->headOfOps]);

        $this->actingAs($this->approver1)
            ->postJson("/api/requisitions/{$requisition->id}/deny", ['remarks' => 'Over budget'])
            ->assertOk();

        Mail::assertQueued(RequisitionCcMail::class, function ($mail) {
            return $mail->hasTo('ops@example.com')
                && $mail->event === RequisitionCcMail::DENIED
                && $mail->remarks === 'Over budget'
                && str_contains($mail->render(), 'Over budget');
        });
    }

    public function test_deleted_contact_still_shows_on_past_requisition(): void
    {
        $requisition = $this->makeRequisition(RequisitionStep::APPROVER_1, [$this->headOfOps]);
        $this->headOfOps->delete();

        $this->actingAs($this->employee)
            ->getJson("/api/requisitions/{$requisition->id}")
            ->assertOk()
            ->assertJsonPath('data.cc_contacts.0.email', 'ops@example.com');
    }
}
