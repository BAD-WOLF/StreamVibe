<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Security;

use App\Domain\User\Entity\User;
use App\Infrastructure\Security\EmailVerifier;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Translation\IdentityTranslator;
use SymfonyCasts\Bundle\VerifyEmail\Model\VerifyEmailSignatureComponents;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * EmailVerifier: o email de confirmação leva à página do front, com os
 * parâmetros e o idioma do link assinado da API.
 *
 * NOTA (nov/2026): criado quando o link deixou de apontar para a API.
 */
final class EmailVerifierTest extends TestCase
{
    public function testTheEmailLinksToTheFrontWithTheSignedParameters(): void
    {
        $user = new User();
        $user->setEmail('ana@example.com')->setPassword('hashed-password');
        $user->id = 42;

        $helper = $this->createStub(VerifyEmailHelperInterface::class);
        $helper->method('generateSignature')->willReturn(new VerifyEmailSignatureComponents(
            new DateTimeImmutable('+1 hour'),
            'http://api.example.com/es/api/verify/email?expires=1700000000&id=42&signature=abc%3D&token=xyz',
            time(),
        ));

        $sent = null;
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send')->willReturnCallback(static function (TemplatedEmail $email) use (&$sent): void {
            $sent = $email;
        });

        new EmailVerifier($helper, $mailer, new IdentityTranslator(), new NullLogger(), 'noreply@example.com', 'StreamVibe', 'https://app.example.com/')
            ->sendConfirmationEmail($user);

        self::assertInstanceOf(TemplatedEmail::class, $sent);
        self::assertSame(
            'https://app.example.com/es/auth/verify-email?expires=1700000000&id=42&signature=abc%3D&token=xyz',
            $sent->getContext()['confirmationUrl'],
        );
        self::assertSame('registration/confirmation_email.html.twig', $sent->getHtmlTemplate());
    }
}
