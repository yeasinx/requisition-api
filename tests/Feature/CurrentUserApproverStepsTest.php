<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\SystemSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentUserApproverStepsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $approver;

    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['role' => UserType::SUPER_ADMIN]);
        $this->approver = User::factory()->create(['role' => UserType::EMPLOYEE]);
        $this->employee = User::factory()->create(['role' => UserType::EMPLOYEE]);

        SystemSettings::create([
            'first_approver_user_id' => $this->approver->id,
            'business_controller_user_id' => $this->approver->id,
            'updated_by_user_id' => $this->superAdmin->id,
        ]);
    }

    public function test_current_user_lists_every_assigned_step_in_workflow_order(): void
    {
        $this->actingAs($this->approver)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.approver_steps', ['APPROVER_1', 'BUSINESS_CONTROLLER']);
    }

    public function test_non_approver_has_no_steps(): void
    {
        $this->actingAs($this->employee)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.approver_steps', []);
    }

    public function test_login_response_includes_the_steps(): void
    {
        $this->postJson('/api/login', ['email' => $this->approver->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('user.approver_steps', ['APPROVER_1', 'BUSINESS_CONTROLLER']);
    }

    public function test_reassignment_is_reflected_immediately(): void
    {
        $this->actingAs($this->superAdmin)
            ->putJson('/api/settings', [
                'first_approver_user_id' => $this->employee->id,
                'business_controller_user_id' => null,
            ])
            ->assertOk();

        $this->actingAs($this->employee)
            ->getJson('/api/user')
            ->assertJsonPath('data.approver_steps', ['APPROVER_1']);

        $this->actingAs($this->approver)
            ->getJson('/api/user')
            ->assertJsonPath('data.approver_steps', []);
    }

    public function test_other_users_assignments_are_not_exposed_in_the_user_list(): void
    {
        $this->actingAs($this->superAdmin)
            ->getJson('/api/users')
            ->assertOk()
            ->assertJsonMissingPath('data.0.approver_steps');
    }
}
