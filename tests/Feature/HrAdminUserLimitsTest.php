<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrAdminUserLimitsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $hrAdmin;

    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['role' => UserType::SUPER_ADMIN]);
        $this->hrAdmin = User::factory()->create(['role' => UserType::HR_ADMIN]);
        $this->employee = User::factory()->create(['role' => UserType::EMPLOYEE]);
    }

    /**
     * @return array<string, string>
     */
    protected function newUser(string $role): array
    {
        return [
            'name' => 'New Person',
            'email' => 'new.person@example.com',
            'password' => 'password123',
            'employee_id' => 'EMP-900',
            'designation' => 'Analyst',
            'role' => $role,
        ];
    }

    public function test_hr_admin_cannot_create_a_super_admin(): void
    {
        $this->actingAs($this->hrAdmin)
            ->postJson('/api/users', $this->newUser(UserType::SUPER_ADMIN->value))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role' => 'Only a Super Admin can grant the Super Admin role.']);

        $this->assertDatabaseMissing('users', ['email' => 'new.person@example.com']);
    }

    public function test_hr_admin_cannot_promote_someone_to_super_admin(): void
    {
        $this->actingAs($this->hrAdmin)
            ->putJson("/api/users/{$this->employee->id}", ['role' => UserType::SUPER_ADMIN->value])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');

        $this->assertSame(UserType::EMPLOYEE, $this->employee->fresh()->role);
    }

    public function test_hr_admin_cannot_change_a_super_admin_account(): void
    {
        $this->actingAs($this->hrAdmin)
            ->putJson("/api/users/{$this->superAdmin->id}", ['password' => 'taken-over-123'])
            ->assertForbidden();
    }

    public function test_hr_admin_still_manages_other_users(): void
    {
        $this->actingAs($this->hrAdmin)
            ->postJson('/api/users', $this->newUser(UserType::ACCOUNTS->value))
            ->assertCreated();

        $this->actingAs($this->hrAdmin)
            ->putJson("/api/users/{$this->employee->id}", ['designation' => 'Lead', 'role' => UserType::HR_ADMIN->value])
            ->assertOk()
            ->assertJsonPath('data.role', UserType::HR_ADMIN->value);
    }

    public function test_super_admin_can_grant_and_manage_super_admins(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson('/api/users', $this->newUser(UserType::SUPER_ADMIN->value))
            ->assertCreated();

        $other = User::factory()->create(['role' => UserType::SUPER_ADMIN]);

        $this->actingAs($this->superAdmin)
            ->putJson("/api/users/{$other->id}", ['designation' => 'Ops'])
            ->assertOk();
    }
}
