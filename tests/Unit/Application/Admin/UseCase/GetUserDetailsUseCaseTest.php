<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Admin\UseCase;

use App\Application\Admin\DTO\User\Details\Request\GetUserDetailsRequest;
use App\Application\Admin\UseCase\User\Details\GetUserDetailsUseCase;
use App\Domain\Authentication\Entity\ResetPasswordSolicitation;
use App\Domain\Authentication\Repository\ResetPasswordSolicitationRepositoryInterface;
use App\Domain\Exception\UserNotFoundException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Service\UserAdministrationPolicy;
use App\Domain\User\ValueObject\Role;
use App\Tests\Unit\Application\Admin\AdminTestCase;
use DateTimeImmutable;
use Symfony\Component\Translation\IdentityTranslator;

/**
 * GetUserDetailsUseCase: todo ROLE_ADMIN vê qualquer conta; can_manage e
 * assignable_roles dizem o que quem pediu pode fazer com ela.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class GetUserDetailsUseCaseTest extends AdminTestCase
{
    public function testEverythingAboutAnAccountTheAdminManages(): void
    {
        $user = $this->user(2, Role::User, blocked: true);
        $user->recordLogin(new DateTimeImmutable('2026-11-07 09:00:00'));
        $resetRequest = new ResetPasswordSolicitation($user, new DateTimeImmutable('+1 hour'), 'selector', 'hashed-token');

        $response = $this->useCase($this->users($this->user(1, Role::Admin), $user), [$resetRequest], activeSessions: 2)
            ->execute(new GetUserDetailsRequest(userId: 2, actorId: 1));

        self::assertSame([2, 'user2@example.com', 'ROLE_USER', true], [$response->id, $response->email, $response->role, $response->verified]);
        self::assertSame($user->getCreatedAt(), $response->createdAt);
        self::assertSame('2026-11-07 09:00:00', $response->lastLoginAt?->format('Y-m-d H:i:s'));
        self::assertSame('2026-11-01 10:00:00', $response->blockedAt?->format('Y-m-d H:i:s'));
        self::assertSame('old reason', $response->blockedReason);
        self::assertSame(2, $response->activeSessions);
        self::assertCount(1, $response->passwordResetRequests);
        self::assertEquals($resetRequest->getExpiresAt(), $response->passwordResetRequests[0]->expiresAt);
        self::assertTrue($response->canManage);
        self::assertSame(['ROLE_USER', 'ROLE_DEV'], $response->assignableRoles);
        self::assertSame('User retrieved successfully', $response->message);
    }

    public function testAnAdminSeesAnOwnerButCannotChangeIt(): void
    {
        $response = $this->useCase($this->users($this->user(1, Role::Admin), $this->user(2, Role::Owner)))
            ->execute(new GetUserDetailsRequest(userId: 2, actorId: 1));

        self::assertSame('ROLE_OWNER', $response->role);
        self::assertFalse($response->canManage);
        self::assertSame([], $response->assignableRoles);
    }

    public function testTheOwnAccountCannotBeChanged(): void
    {
        $response = $this->useCase($this->users($this->user(1, Role::Owner)))
            ->execute(new GetUserDetailsRequest(userId: 1, actorId: 1));

        self::assertFalse($response->canManage);
        self::assertSame([], $response->assignableRoles);
    }

    public function testAnUnknownAccountIsNotFound(): void
    {
        $this->expectException(UserNotFoundException::class);

        $this->useCase($this->users($this->user(1, Role::Admin)))
            ->execute(new GetUserDetailsRequest(userId: 9, actorId: 1));
    }

    /**
     * @param list<\App\Domain\Authentication\Entity\ResetPasswordSolicitation> $resetRequests
     */
    private function useCase(UserRepositoryInterface $users, array $resetRequests = [], int $activeSessions = 0): GetUserDetailsUseCase
    {
        $resetRequestRepository = $this->createStub(ResetPasswordSolicitationRepositoryInterface::class);
        $resetRequestRepository->method('findActiveRequestsForUser')->willReturn($resetRequests);

        return new GetUserDetailsUseCase(
            accounts: $this->accounts($users),
            policy: new UserAdministrationPolicy(),
            sessions: $this->sessions($activeSessions),
            resetRequestRepository: $resetRequestRepository,
            requestValidator: $this->validator(),
            translator: new IdentityTranslator(),
        );
    }
}
