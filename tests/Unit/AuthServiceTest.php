<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    protected User|MockInterface $userModel;

    protected Builder|MockInterface $queryBuilder;

    protected AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userModel = Mockery::mock(User::class);
        $this->queryBuilder = Mockery::mock(Builder::class);

        $this->userModel->shouldReceive('newQuery')
            ->byDefault()
            ->andReturn($this->queryBuilder);

        $this->authService = new AuthService($this->userModel);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_attempt_login_throws_validation_exception_when_user_not_found(): void
    {
        $this->queryBuilder->shouldReceive('where')
            ->with('email', 'unknown@example.com')
            ->once()
            ->andReturnSelf();

        $this->queryBuilder->shouldReceive('first')
            ->once()
            ->andReturnNull();

        $this->expectException(ValidationException::class);

        $this->authService->attemptLogin('unknown@example.com', 'secret123');
    }

    public function test_attempt_login_throws_validation_exception_when_password_incorrect(): void
    {
        $user = new User;
        $user->email = 'user@example.com';
        $user->setRawAttributes(['password' => '$2y$12$hashed_password_sample']);

        $this->queryBuilder->shouldReceive('where')
            ->with('email', 'user@example.com')
            ->once()
            ->andReturnSelf();

        $this->queryBuilder->shouldReceive('first')
            ->once()
            ->andReturn($user);

        Hash::shouldReceive('check')
            ->with('wrong_password', '$2y$12$hashed_password_sample')
            ->once()
            ->andReturnFalse();

        $this->expectException(ValidationException::class);

        $this->authService->attemptLogin('user@example.com', 'wrong_password');
    }

    public function test_attempt_login_returns_user_and_token_on_valid_credentials(): void
    {
        $newAccessToken = new NewAccessToken(
            new PersonalAccessToken,
            '1|sample_plain_text_token'
        );

        $user = new class extends User
        {
            public ?NewAccessToken $tokenInstance = null;

            public function createToken(string $name, array $abilities = ['*'], ?\DateTimeInterface $expiresAt = null): NewAccessToken
            {
                return $this->tokenInstance;
            }
        };

        $user->tokenInstance = $newAccessToken;
        $user->email = 'user@example.com';
        $user->setRawAttributes(['password' => '$2y$12$hashed_password_sample']);

        $this->queryBuilder->shouldReceive('where')
            ->with('email', 'user@example.com')
            ->once()
            ->andReturnSelf();

        $this->queryBuilder->shouldReceive('first')
            ->once()
            ->andReturn($user);

        Hash::shouldReceive('check')
            ->with('correct_password', '$2y$12$hashed_password_sample')
            ->once()
            ->andReturnTrue();

        $result = $this->authService->attemptLogin('user@example.com', 'correct_password');

        $this->assertSame($user, $result['user']);
        $this->assertSame('1|sample_plain_text_token', $result['token']);
    }

    public function test_logout_revokes_current_access_token(): void
    {
        $token = Mockery::mock(PersonalAccessToken::class);
        $token->shouldReceive('delete')->once()->andReturnTrue();

        $user = new class extends User
        {
            public $mockToken = null;

            public function currentAccessToken()
            {
                return $this->mockToken;
            }
        };

        $user->mockToken = $token;

        $this->authService->logout($user);
        $this->assertTrue(true);
    }
}
