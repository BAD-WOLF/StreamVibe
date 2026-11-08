<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Security;

use App\Domain\User\Entity\User;
use App\Infrastructure\Security\UserCheckerAuthLogin;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Translation\IdentityTranslator;

/**
 * UserCheckerAuthLogin: a conta sem email confirmado só é recusada depois
 * da senha certa.
 *
 * NOTA (nov/2026): criado quando a conferência saiu do checkPreAuth(). Lá,
 * antes da senha, a resposta "confirme seu email" dizia a qualquer pessoa
 * que aquele email tinha conta.
 */
final class UserCheckerAuthLoginTest extends TestCase
{
    public function testBeforeThePasswordNothingIsRevealed(): void
    {
        new UserCheckerAuthLogin(new IdentityTranslator())->checkPreAuth($this->makeUser(verified: false));

        $this->addToAssertionCount(1);
    }

    public function testAfterTheRightPasswordAnUnverifiedAccountIsRefused(): void
    {
        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Check your email to activate your account!!');

        new UserCheckerAuthLogin(new IdentityTranslator())->checkPostAuth($this->makeUser(verified: false));
    }

    public function testAVerifiedAccountPasses(): void
    {
        $checker = new UserCheckerAuthLogin(new IdentityTranslator());
        $user = $this->makeUser(verified: true);

        $checker->checkPreAuth($user);
        $checker->checkPostAuth($user);

        $this->addToAssertionCount(1);
    }

    /**
     * NOTA (nov/2026): a conta bloqueada também só é recusada depois da
     * senha certa — e antes da confirmação do email, que não adiantaria.
     */
    public function testABlockedAccountIsRefusedOnlyAfterTheRightPassword(): void
    {
        $checker = new UserCheckerAuthLogin(new IdentityTranslator());
        $user = $this->makeUser(verified: false);
        $user->block('spam', new \DateTimeImmutable());

        $checker->checkPreAuth($user);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('This account has been blocked.');

        $checker->checkPostAuth($user);
    }

    private function makeUser(bool $verified): User
    {
        $user = new User();
        $user->setEmail('ana@example.com');
        $user->setPassword('hashed-password');
        $user->setIsVerified($verified);

        return $user;
    }
}
