<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Admin\UseCase;

use App\Application\Admin\DTO\User\ResendVerification\Request\ResendUserVerificationRequest;
use App\Application\Admin\DTO\User\Sessions\Request\RevokeUserSessionsRequest;
use App\Application\Admin\DTO\User\Verify\Request\VerifyUserRequest;
use App\Application\Admin\UseCase\User\ResendVerification\ResendUserVerificationUseCase;
use App\Application\Admin\UseCase\User\Sessions\RevokeUserSessionsUseCase;
use App\Application\Admin\UseCase\User\Verify\VerifyUserUseCase;
use App\Application\Authentication\Port\EmailVerificationInterface;
use App\Domain\Exception\BusinessLogicException;
use App\Domain\Exception\EmailDeliveryException;
use App\Domain\User\ValueObject\Role;
use App\Tests\Unit\Application\Admin\AdminTestCase;
use Symfony\Component\Translation\IdentityTranslator;

/**
 * As ações que não têm regra além de quem administra quem: confirmar o
 * email (VerifyUserUseCase), reenviar a confirmação
 * (ResendUserVerificationUseCase) e encerrar as sessões
 * (RevokeUserSessionsUseCase).
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class VerifyUserUseCaseTest extends AdminTestCase
{
    public function testVerifiesTheAccount(): void
    {
        $user = $this->user(2, Role::User, verified: false);
        $users = $this->users($this->user(1, Role::Admin), $user);

        $response = new VerifyUserUseCase($this->accounts($users), $users, $this->auditTrail(), $this->validator(), new IdentityTranslator())
            ->execute(new VerifyUserRequest(userId: 2, actorId: 1));

        self::assertTrue($user->isVerified());
        self::assertSame([2, true], [$response->userId, $response->verified]);
        self::assertSame(['audit:user_verified:no-flush', 'save:2'], $this->events);
        self::assertSame(['role' => 'ROLE_USER'], $this->onlyEntry()->details);
    }

    public function testAVerifiedAccountIsNotVerifiedAgain(): void
    {
        $users = $this->users($this->user(1, Role::Admin), $this->user(2, Role::User));

        $this->expectException(BusinessLogicException::class);
        $this->expectExceptionCode(409);

        new VerifyUserUseCase($this->accounts($users), $users, $this->auditTrail(), $this->validator(), new IdentityTranslator())
            ->execute(new VerifyUserRequest(userId: 2, actorId: 1));
    }

    public function testResendsTheConfirmation(): void
    {
        $user = $this->user(2, Role::User, verified: false);
        $emailVerification = $this->createMock(EmailVerificationInterface::class);
        $emailVerification->expects($this->once())->method('sendConfirmationEmail')->with($user, true);

        $response = new ResendUserVerificationUseCase(
            $this->accounts($this->users($this->user(1, Role::Admin), $user)),
            $emailVerification,
            $this->auditTrail(),
            $this->validator(),
            new IdentityTranslator(),
        )->execute(new ResendUserVerificationRequest(userId: 2, actorId: 1));

        self::assertSame([2, true], [$response->userId, $response->emailSent]);
        self::assertSame(['audit:verification_resent:flush'], $this->events);
    }

    /**
     * O email não saiu (503): nada entra no log.
     */
    public function testAFailedEmailIsNotLogged(): void
    {
        $emailVerification = $this->createStub(EmailVerificationInterface::class);
        $emailVerification->method('sendConfirmationEmail')->willThrowException(new EmailDeliveryException('smtp down'));

        try {
            new ResendUserVerificationUseCase(
                $this->accounts($this->users($this->user(1, Role::Admin), $this->user(2, Role::User, verified: false))),
                $emailVerification,
                $this->auditTrail(),
                $this->validator(),
                new IdentityTranslator(),
            )->execute(new ResendUserVerificationRequest(userId: 2, actorId: 1));
            self::fail('the delivery error should reach the caller');
        } catch (EmailDeliveryException) {
        }

        self::assertSame([], $this->events);
    }

    public function testEndsTheSessionsEvenWhenThereAreNone(): void
    {
        $users = $this->users($this->user(1, Role::Owner), $this->user(2, Role::Admin));

        $response = new RevokeUserSessionsUseCase($this->accounts($users), $this->sessions(0), $this->auditTrail(), $this->validator(), new IdentityTranslator())
            ->execute(new RevokeUserSessionsRequest(userId: 2, actorId: 1));

        self::assertSame([2, 0], [$response->userId, $response->revokedSessions]);
        self::assertSame(['revoke:2', 'audit:sessions_revoked:flush'], $this->events);
        self::assertSame(['role' => 'ROLE_ADMIN', 'revoked_sessions' => 0], $this->onlyEntry()->details);
    }
}
