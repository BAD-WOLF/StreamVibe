<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Account\UseCase\CurrentUser;

use App\Application\Account\DTO\CurrentUser\Request\GetCurrentUserRequest;
use App\Application\Account\UseCase\CurrentUser\GetCurrentUserUseCase;
use App\Application\Shared\RequestValidator;
use App\Domain\Exception\UserNotFoundException;
use App\Domain\Exception\UserValidationException;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\Validation;

/**
 * GetCurrentUserUseCase (GET /me): os dados vêm do banco, e `role` é o
 * papel da conta na hierarquia.
 *
 * NOTA (nov/2026): criado com o GET /me. Com a hierarquia de papéis, `roles`
 * (a lista guardada) virou `role`.
 */
final class GetCurrentUserUseCaseTest extends TestCase
{
    public function testReturnsTheAccountReadFromTheDatabase(): void
    {
        $user = $this->makeUser(id: 42, roles: ['ROLE_ADMIN'], verified: true);

        $response = $this->makeUseCase($this->userRepositoryReturning($user))
            ->execute(new GetCurrentUserRequest(userId: 42));

        self::assertSame(42, $response->id);
        self::assertSame('user42@example.com', $response->email);
        self::assertSame('ROLE_ADMIN', $response->role);
        self::assertTrue($response->verified);
        self::assertSame('Current user retrieved successfully', $response->message);
    }

    /**
     * Uma conta com mais de um papel guardado (de antes de assignRole(), que
     * guarda um só) é do mais alto; sem nenhum, ROLE_USER.
     */
    public function testTheRoleIsTheHighestStoredOne(): void
    {
        $user = $this->makeUser(id: 7, roles: ['ROLE_DEV', 'ROLE_ADMIN', 'ROLE_DEV'], verified: false);

        $response = $this->makeUseCase($this->userRepositoryReturning($user))
            ->execute(new GetCurrentUserRequest(userId: 7));

        self::assertSame('ROLE_ADMIN', $response->role);
        self::assertFalse($response->verified);

        $plain = $this->makeUseCase($this->userRepositoryReturning($this->makeUser(id: 8, roles: [], verified: true)))
            ->execute(new GetCurrentUserRequest(userId: 8));

        self::assertSame('ROLE_USER', $plain->role);
    }

    public function testInvalidUserIdIsRejected(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->never())->method('findById');

        try {
            $this->makeUseCase($userRepository)->execute(new GetCurrentUserRequest(userId: 0));
            self::fail('userId 0 should be rejected');
        } catch (UserValidationException $exception) {
            self::assertSame(400, $exception->getCode());
            self::assertSame(['User_id: User ID must be a positive integer'], $exception->getErrors());
        }
    }

    public function testUnknownUserIsNotFound(): void
    {
        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionCode(404);

        $this->makeUseCase($this->userRepositoryReturning(null))
            ->execute(new GetCurrentUserRequest(userId: 42));
    }

    private function makeUseCase(UserRepositoryInterface $userRepository): GetCurrentUserUseCase
    {
        return new GetCurrentUserUseCase(
            userRepository: $userRepository,
            requestValidator: new RequestValidator(
                Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
                new NullLogger(),
            ),
            translator: new IdentityTranslator(),
        );
    }

    private function userRepositoryReturning(?User $user): UserRepositoryInterface
    {
        $userRepository = $this->createStub(UserRepositoryInterface::class);
        $userRepository->method('findById')->willReturn($user);

        return $userRepository;
    }

    /**
     * @param list<string> $roles
     */
    private function makeUser(int $id, array $roles, bool $verified): User
    {
        $user = new User();
        $user->setEmail("user{$id}@example.com");
        $user->setPassword('hashed-password');
        $user->setRoles($roles);
        $user->setIsVerified($verified);
        $user->id = $id;

        return $user;
    }
}
