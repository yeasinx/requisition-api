<?php

namespace Tests\Feature;

use App\Enums\RequisitionStatus;
use App\Enums\RequisitionStep;
use App\Enums\UserType;
use App\Models\Requisition;
use App\Models\SystemSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequisitionTrashedListingTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $employee;

    protected Requisition $active;

    protected Requisition $deleted;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['role' => UserType::SUPER_ADMIN]);
        $this->employee = User::factory()->create(['role' => UserType::EMPLOYEE]);

        SystemSettings::create(['updated_by_user_id' => $this->superAdmin->id]);

        $this->active = $this->makeRequisition('REQ-2026-0001');
        $this->deleted = $this->makeRequisition('REQ-2026-0002');
        $this->deleted->delete();
    }

    protected function makeRequisition(string $number): Requisition
    {
        return Requisition::create([
            'requisition_number' => $number,
            'submitted_by_user_id' => $this->employee->id,
            'current_step' => RequisitionStep::APPROVER_1,
            'status' => RequisitionStatus::PENDING,
            'total_expected_price' => 100.00,
        ]);
    }

    public function test_super_admin_sees_only_deleted_requisitions_with_trashed_only(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/requisitions?trashed=only');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->deleted->id)
            ->assertJsonPath('data.0.deleted_at', fn ($value) => $value !== null);
    }

    public function test_super_admin_does_not_see_deleted_requisitions_by_default(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/requisitions');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->active->id);
    }

    public function test_trashed_only_is_ignored_for_non_super_admin(): void
    {
        $response = $this->actingAs($this->employee)
            ->getJson('/api/requisitions?trashed=only');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->active->id);
    }
}
