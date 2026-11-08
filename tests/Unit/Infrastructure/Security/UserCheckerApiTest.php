<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Security;

use App\Domain\User\Entity\User;
use App\Infrastructure\Security\UserCheckerApi;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Translation\IdentityTranslator;

/**
 * UserCheckerApi: no firewall "api", a conta bloqueada é recusada antes de
 * o token ser aceito (a cada requisição e a cada renovação).
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class UserCheckerApiTest extends TestCase
{
    public function testABlockedAccountIsRefused(): void
    {
        $user = $this->makeUser();
        $user->block('spam', new DateTimeImmutable());

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('This account has been blocked.');

        new UserCheckerApi(new IdentityTranslator())->checkPreAuth($user);
    }

    public function testAnAccountThatIsNotBlockedPasses(): void
    {
        $checker = new UserCheckerApi(new IdentityTranslator());
        $user = $this->makeUser();

        $checker->checkPreAuth($user);
        $checker->checkPostAuth($user);
        $checker->checkPreAuth(new InMemoryUser('someone', null));

        $this->addToAssertionCount(1);
    }

    private function makeUser(): User
    {
        $user = new User();
        $user->setEmail('ana@example.com')->setPassword('hashed-password')->setIsVerified(true);

        return $user;
    }
}
