<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Admin\UseCase;

use App\Application\Admin\DTO\User\Block\Request\BlockUserRequest;
use App\Application\Admin\DTO\User\Unblock\Request\UnblockUserRequest;
use App\Application\Admin\UseCase\User\Block\BlockUserUseCase;
use App\Application\Admin\UseCase\User\Unblock\UnblockUserUseCase;
use App\Domain\Exception\BusinessLogicException;
use App\Domain\Exception\UserNotFoundException;
use App\Domain\Exception\UserValidationException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Service\UserAdministrationPolicy;
use App\Domain\User\ValueObject\Role;
use App\Tests\Unit\Application\Admin\AdminTestCase;
use Symfony\Component\Translation\IdentityTranslator;

/**
 * BlockUserUseCase e UnblockUserUseCase: o bloqueio encerra as sessões
 * antes de gravar, guarda o motivo e entra no log; o desbloqueio tira o
 * motivo da conta e o deixa no log.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class BlockUserUseCaseTest extends AdminTestCase
{
    public function testBlocksEndsTheSessionsAndRecordsTheReason(): void
    {
        $user = $this->user(2, Role::User);

        $response = $this->block($this->users($this->user(1, Role::Admin), $user), activeSessions: 3)
            ->execute(new BlockUserRequest(userId: 2, actorId: 1, reason: '  spam  '));

        self::assertTrue($user->isBlocked());
        self::assertSame('spam', $user->getBlockedReason());
        self::assertSame($user->getBlockedAt(), $response->blockedAt);
        self::assertSame(3, $response->revokedSessions);
        self::assertSame(['revoke:2', 'audit:user_blocked:no-flush', 'save:2'], $this->events);
        self::assertSame(['role' => 'ROLE_USER', 'reason' => 'spam', 'revoked_sessions' => 3], $this->onlyEntry()->details);
    }

    public function testABlockedAccountIsNotBlockedAgain(): void
    {
        $this->expectException(BusinessLogicException::class);
        $this->expectExceptionMessage('This account is already blocked');

        $this->block($this->users($this->user(1, Role::Admin), $this->user(2, Role::User, blocked: true)))
            ->execute(new BlockUserRequest(userId: 2, actorId: 1, reason: 'spam'));
    }

    public function testTheReasonIsRequired(): void
    {
        try {
            $this->block($this->users($this->user(1, Role::Admin), $this->user(2, Role::User)))
                ->execute(new BlockUserRequest(userId: 2, actorId: 1, reason: '   '));
            self::fail('a blank reason should be rejected');
        } catch (UserValidationException $exception) {
            self::assertSame(['Reason: Reason cannot be blank'], $exception->getErrors());
        }

        self::assertSame([], $this->events);
    }

    public function testAnUnknownAccountIsNotFound(): void
    {
        $this->expectException(UserNotFoundException::class);

        $this->block($this->users($this->user(1, Role::Admin)))
            ->execute(new BlockUserRequest(userId: 99, actorId: 1, reason: 'spam'));
    }

    public function testUnblockKeepsTheOldReasonInTheLog(): void
    {
        $user = $this->user(2, Role::Dev, blocked: true);
        $users = $this->users($this->user(1, Role::Admin), $user);

        $response = new UnblockUserUseCase($this->accounts($users), $users, $this->auditTrail(), $this->validator(), new IdentityTranslator())
            ->execute(new UnblockUserRequest(userId: 2, actorId: 1));

        self::assertFalse($user->isBlocked());
        self::assertNull($user->getBlockedReason());
        self::assertNull($response->blockedAt);
        self::assertSame(['audit:user_unblocked:no-flush', 'save:2'], $this->events);
        self::assertSame(['role' => 'ROLE_DEV', 'previous_reason' => 'old reason'], $this->onlyEntry()->details);
    }

    public function testAnAccountThatIsNotBlockedIsNotUnblocked(): void
    {
        $users = $this->users($this->user(1, Role::Admin), $this->user(2, Role::User));

        $this->expectException(BusinessLogicException::class);
        $this->expectExceptionMessage('This account is not blocked');

        new UnblockUserUseCase($this->accounts($users), $users, $this->auditTrail(), $this->validator(), new IdentityTranslator())
            ->execute(new UnblockUserRequest(userId: 2, actorId: 1));
    }

    private function block(UserRepositoryInterface $users, int $activeSessions = 0): BlockUserUseCase
    {
        return new BlockUserUseCase(
            accounts: $this->accounts($users),
            userRepository: $users,
            policy: new UserAdministrationPolicy(),
            sessions: $this->sessions($activeSessions),
            auditTrail: $this->auditTrail(),
            requestValidator: $this->validator(),
            translator: new IdentityTranslator(),
        );
    }
}
