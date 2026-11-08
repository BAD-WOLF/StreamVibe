<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Security;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Security\LastLoginListener;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * LastLoginListener: o login certo registra a data e grava a conta.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class LastLoginListenerTest extends TestCase
{
    public function testRecordsTheLogin(): void
    {
        $user = new User();
        $user->setEmail('ana@example.com')->setPassword('hashed-password');
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())->method('save')->with($user);
        $before = new DateTimeImmutable();

        new LastLoginListener($repository)->onLoginSuccess($this->event($user));

        self::assertNotNull($user->getLastLoginAt());
        self::assertGreaterThanOrEqual($before, $user->getLastLoginAt());
    }

    public function testAnotherKindOfUserIsIgnored(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->never())->method('save');

        new LastLoginListener($repository)->onLoginSuccess($this->event(new InMemoryUser('someone', null)));
    }

    private function event(UserInterface $user): LoginSuccessEvent
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return new LoginSuccessEvent(
            $this->createStub(AuthenticatorInterface::class),
            new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), static fn(): UserInterface => $user)),
            $token,
            new Request(),
            null,
            'login',
        );
    }
}
