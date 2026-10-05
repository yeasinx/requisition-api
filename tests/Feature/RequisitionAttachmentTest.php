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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RequisitionAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $approver;

    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(RequisitionAttachment::DISK);
        Mail::fake();

        $this->approver = User::factory()->create(['role' => UserType::EMPLOYEE]);
        $this->employee = User::factory()->create(['role' => UserType::EMPLOYEE]);

        SystemSettings::create([
            'first_approver_user_id' => $this->approver->id,
            'updated_by_user_id' => $this->approver->id,
        ]);
    }

    protected function makeRequisition(string $number = 'REQ-2026-0001'): Requisition
    {
        return Requisition::create([
            'requisition_number' => $number,
            'submitted_by_user_id' => $this->employee->id,
            'current_step' => RequisitionStep::APPROVER_1,
            'status' => RequisitionStatus::PENDING,
            'total_expected_price' => 100.00,
        ]);
    }

    protected function pdf(string $name = 'quote.pdf', int $kb = 100): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kb, 'application/pdf');
    }

    protected function upload(Requisition $requisition, array $files, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->employee)
            ->post("/api/requisitions/{$requisition->id}/attachments", ['attachments' => $files], ['Accept' => 'application/json']);
    }

    public function test_requisition_can_be_created_with_attachments(): void
    {
        $response = $this->actingAs($this->employee)->post('/api/requisitions', [
            'items' => [
                ['item_name' => 'Laptop', 'description' => 'Dev machine', 'quantity' => 1, 'unit_price' => 1000],
            ],
            'attachments' => [
                $this->pdf(),
                UploadedFile::fake()->create('budget.xlsx', 50, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            ],
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonCount(2, 'data.attachments')
            ->assertJsonPath('data.attachments.0.original_name', 'quote.pdf')
            ->assertJsonPath('data.attachments.0.uploaded_by.id', $this->employee->id);

        $attachments = RequisitionAttachment::all();
        $this->assertCount(2, $attachments);
        $attachments->each(fn ($a) => Storage::disk(RequisitionAttachment::DISK)->assertExists($a->path));
    }

    public function test_requisition_can_still_be_created_as_json_without_attachments(): void
    {
        $this->actingAs($this->employee)->postJson('/api/requisitions', [
            'items' => [
                ['item_name' => 'Laptop', 'description' => 'Dev machine', 'quantity' => 1, 'unit_price' => 1000],
            ],
        ])->assertCreated()->assertJsonCount(0, 'data.attachments');
    }

    public function test_attachments_are_optional_when_sent_empty(): void
    {
        $items = [
            ['item_name' => 'Laptop', 'description' => 'Dev machine', 'quantity' => 1, 'unit_price' => 1000],
        ];

        $this->actingAs($this->employee)
            ->postJson('/api/requisitions', ['items' => $items, 'attachments' => null])
            ->assertCreated()
            ->assertJsonCount(0, 'data.attachments');

        // Multipart form with the file field left blank
        $this->actingAs($this->employee)
            ->post('/api/requisitions', ['items' => $items, 'attachments' => ''], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonCount(0, 'data.attachments');

        $this->assertDatabaseCount('requisition_attachments', 0);
    }

    public function test_disallowed_file_types_and_oversized_files_are_rejected(): void
    {
        $requisition = $this->makeRequisition();

        $this->upload($requisition, [UploadedFile::fake()->create('photo.png', 10, 'image/png')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attachments.0');

        $this->upload($requisition, [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')])
            ->assertUnprocessable();

        $this->upload($requisition, [$this->pdf('huge.pdf', RequisitionAttachment::MAX_SIZE_KB + 1)])
            ->assertUnprocessable();

        $this->assertDatabaseCount('requisition_attachments', 0);
    }

    public function test_attachment_limit_applies_across_uploads(): void
    {
        $requisition = $this->makeRequisition();

        $this->upload($requisition, array_map(fn ($i) => $this->pdf("q{$i}.pdf"), range(1, 4)))->assertCreated();

        $this->upload($requisition, [$this->pdf('a.pdf'), $this->pdf('b.pdf')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attachments');

        $this->upload($requisition, [$this->pdf('fifth.pdf')])->assertCreated();

        $this->assertDatabaseCount('requisition_attachments', 5);
    }

    public function test_submitter_can_add_and_delete_attachments_before_any_approval(): void
    {
        $requisition = $this->makeRequisition();

        $id = $this->upload($requisition, [$this->pdf()])->assertCreated()->json('data.0.id');
        $path = RequisitionAttachment::find($id)->path;

        $this->actingAs($this->employee)
            ->deleteJson("/api/requisitions/{$requisition->id}/attachments/{$id}")
            ->assertOk();

        $this->assertDatabaseMissing('requisition_attachments', ['id' => $id]);
        Storage::disk(RequisitionAttachment::DISK)->assertMissing($path);
    }

    public function test_attachments_cannot_be_changed_after_an_approval_or_by_others(): void
    {
        $requisition = $this->makeRequisition();
        $id = $this->upload($requisition, [$this->pdf()])->assertCreated()->json('data.0.id');

        $this->upload($requisition, [$this->pdf()], $this->approver)->assertForbidden();

        ApprovalStep::create([
            'requisition_id' => $requisition->id,
            'step_type' => RequisitionStep::APPROVER_1,
            'acted_by_user_id' => $this->approver->id,
            'decision' => DecisionStatus::APPROVED,
            'acted_at' => now(),
        ]);

        $this->upload($requisition, [$this->pdf()])->assertForbidden();
        $this->actingAs($this->employee)
            ->deleteJson("/api/requisitions/{$requisition->id}/attachments/{$id}")
            ->assertForbidden();
    }

    public function test_assigned_approver_can_download_but_unrelated_user_cannot(): void
    {
        $requisition = $this->makeRequisition();
        $id = $this->upload($requisition, [$this->pdf('quote.pdf')])->assertCreated()->json('data.0.id');

        $this->actingAs($this->approver)
            ->get("/api/requisitions/{$requisition->id}/attachments/{$id}")
            ->assertOk()
            ->assertDownload('quote.pdf');

        $stranger = User::factory()->create(['role' => UserType::EMPLOYEE]);
        $this->actingAs($stranger)
            ->getJson("/api/requisitions/{$requisition->id}/attachments/{$id}")
            ->assertForbidden();
    }

    public function test_attachment_from_another_requisition_is_not_found(): void
    {
        $requisition = $this->makeRequisition();
        $other = $this->makeRequisition('REQ-2026-0002');
        $id = $this->upload($other, [$this->pdf()])->assertCreated()->json('data.0.id');

        $this->actingAs($this->employee)
            ->getJson("/api/requisitions/{$requisition->id}/attachments/{$id}")
            ->assertNotFound();
    }
}
