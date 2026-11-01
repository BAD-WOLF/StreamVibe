<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Authentication\UseCase\ResetPassword\Solicitation;

use App\Application\Authentication\DTO\ResetPassword\Solicitation\Request\RequestPasswordResetRequest;
use App\Application\Authentication\Port\PasswordResetInterface;
use App\Application\Authentication\Port\PasswordResetToken;
use App\Application\Authentication\UseCase\ResetPassword\Solicitation\RequestPasswordResetUseCase;
use App\Application\Shared\RequestValidator;
use App\Domain\Authentication\Entity\ResetPasswordSolicitation;
use App\Domain\Authentication\Repository\ResetPasswordSolicitationRepositoryInterface;
use App\Domain\Exception\EmailDeliveryException;
use App\Domain\Exception\UserNotVerifiedException;
use App\Domain\Exception\UserValidationException;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\Validation;

/**
 * RequestPasswordResetUseCase (POST /reset-password) isolado: cada ramo
 * do pedido de troca de senha, com as portas como dublês e a validação
 * real (Symfony Validator lendo os atributos do Request DTO).
 *
 * NOTA (out/2026): substitui parte do antigo ResetUserPasswordUseCaseTest
 * (o UseCase foi dividido em cinco). Cobre também o que o antigo não
 * cobria: link novo enviado, falha no envio do email (o link recém-criado
 * é apagado e a resposta é 503) e a limpeza das solicitações vencidas.
 */
final class RequestPasswordResetUseCaseTest extends TestCase
{
    public function testUnknownEmailAnswersGenericMessageWithoutCreatingALink(): void
    {
        $passwordReset = $this->createMock(PasswordResetInterface::class);
        $passwordReset->expects($this->never())->method("generateToken");
        $passwordReset->expects($this->never())->method("sendResetEmail");

        $response = $this->makeUseCase(
            $this->userRepositoryReturning(null),
            $this->createStub(ResetPasswordSolicitationRepositoryInterface::class),
            $passwordReset,
        )->execute(new RequestPasswordResetRequest(email: "ghost@example.com"));

        self::assertFalse($response->emailSent);
        self::assertNull($response->expiresAt);
        self::assertSame(
            "If an account with this email exists, a password reset link has been sent.",
            $response->message,
        );
    }

    public function testUnverifiedAccountIsRejectedWith403(): void
    {
        $passwordReset = $this->createMock(PasswordResetInterface::class);
        $passwordReset->expects($this->never())->method("generateToken");

        $useCase = $this->makeUseCase(
            $this->userRepositoryReturning($this->makeUser(verified: false)),
            $this->createStub(ResetPasswordSolicitationRepositoryInterface::class),
            $passwordReset,
        );

        $this->expectException(UserNotVerifiedException::class);
        $this->expectExceptionCode(403);

        $useCase->execute(new RequestPasswordResetRequest(email: "unverified@example.com"));
    }

    public function testActiveRequestAnswersItsExpirationWithoutSendingAnotherEmail(): void
    {
        $user = $this->makeUser();
        $expiresAt = new DateTimeImmutable("+40 minutes");

        $resetRequestRepository = $this->createMock(ResetPasswordSolicitationRepositoryInterface::class);
        $resetRequestRepository->expects($this->once())
            ->method("removeExpiredRequestsFor")
            ->with($user)
            ->willReturn(1);
        $resetRequestRepository->method("findActiveRequestsForUser")
            ->willReturn([$this->makeSolicitation($user, $expiresAt)]);

        $passwordReset = $this->createMock(PasswordResetInterface::class);
        $passwordReset->expects($this->never())->method("generateToken");
        $passwordReset->expects($this->never())->method("sendResetEmail");

        $response = $this->makeUseCase(
            $this->userRepositoryReturning($user),
            $resetRequestRepository,
            $passwordReset,
        )->execute(new RequestPasswordResetRequest(email: "user@example.com"));

        self::assertFalse($response->emailSent);
        self::assertEquals($expiresAt, $response->expiresAt);
    }

    public function testNewLinkIsGeneratedAndSent(): void
    {
        $user = $this->makeUser();
        $token = $this->makeToken();

        $passwordReset = $this->createMock(PasswordResetInterface::class);
        $passwordReset->expects($this->once())
            ->method("generateToken")
            ->with($user)
            ->willReturn($token);
        $passwordReset->expects($this->once())
            ->method("sendResetEmail")
            ->with($user, $token);
        $passwordReset->expects($this->never())->method("removeToken");

        $response = $this->makeUseCase(
            $this->userRepositoryReturning($user),
            $this->createStub(ResetPasswordSolicitationRepositoryInterface::class),
            $passwordReset,
        )->execute(new RequestPasswordResetRequest(email: "user@example.com"));

        self::assertTrue($response->emailSent);
        self::assertSame($token->expiresAt, $response->expiresAt);
        self::assertSame(
            "A password reset link has been sent to your email address.",
            $response->message,
        );
    }

    /**
     * Sem o email, o link novo ficaria "ativo" e o próximo pedido
     * responderia "já existe um link válido" — com o usuário sem link
     * nenhum na caixa de entrada.
     */
    public function testLinkIsRemovedWhenTheEmailCannotBeSent(): void
    {
        $user = $this->makeUser();
        $token = $this->makeToken();

        $passwordReset = $this->createMock(PasswordResetInterface::class);
        $passwordReset->method("generateToken")->willReturn($token);
        $passwordReset->method("sendResetEmail")
            ->willThrowException(new EmailDeliveryException("Failed to send the password reset email"));
        $passwordReset->expects($this->once())
            ->method("removeToken")
            ->with($token->token);

        $useCase = $this->makeUseCase(
            $this->userRepositoryReturning($user),
            $this->createStub(ResetPasswordSolicitationRepositoryInterface::class),
            $passwordReset,
        );

        $this->expectException(EmailDeliveryException::class);
        $this->expectExceptionCode(503);

        $useCase->execute(new RequestPasswordResetRequest(email: "user@example.com"));
    }

    public function testInvalidEmailIsRejectedBeforeAnyLookup(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->never())->method("findByEmail");

        $useCase = $this->makeUseCase(
            $userRepository,
            $this->createStub(ResetPasswordSolicitationRepositoryInterface::class),
            $this->createStub(PasswordResetInterface::class),
        );

        try {
            $useCase->execute(new RequestPasswordResetRequest(email: "not-an-email"));
            self::fail("An invalid email should be rejected");
        } catch (UserValidationException $exception) {
            self::assertSame(400, $exception->getCode());
            self::assertSame(
                ["Email: Please provide a valid email address"],
                $exception->getErrors(),
            );
        }
    }

    private function makeUseCase(
        UserRepositoryInterface $userRepository,
        ResetPasswordSolicitationRepositoryInterface $resetRequestRepository,
        PasswordResetInterface $passwordReset,
    ): RequestPasswordResetUseCase {
        return new RequestPasswordResetUseCase(
            userRepository: $userRepository,
            resetRequestRepository: $resetRequestRepository,
            passwordReset: $passwordReset,
            requestValidator: new RequestValidator(
                Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
                new NullLogger(),
            ),
            logger: new NullLogger(),
            translator: new IdentityTranslator(),
        );
    }

    private function userRepositoryReturning(?User $user): UserRepositoryInterface
    {
        $userRepository = $this->createStub(UserRepositoryInterface::class);
        $userRepository->method("findByEmail")->willReturn($user);

        return $userRepository;
    }

    private function makeUser(bool $verified = true): User
    {
        $user = new User();
        $user->setEmail("user@example.com");
        $user->setPassword("hashed-password");
        $user->setIsVerified($verified);
        $user->id = 42;

        return $user;
    }

    private function makeToken(): PasswordResetToken
    {
        return new PasswordResetToken(
            token: str_repeat("a", 40),
            expiresAt: new DateTimeImmutable("+1 hour"),
            expirationMessageKey: "%count% hour|%count% hours",
            expirationMessageData: ["%count%" => 1],
        );
    }

    /**
     * Entidade real, não dublê: no PHPUnit 12 + PHP 8.4 o gerador de
     * dublês tenta declarar "implements DateTimeInterface" por causa dos
     * métodos do ResetPasswordRequestTrait que devolvem DateTimeInterface,
     * e isso é erro fatal para classe de usuário.
     */
    private function makeSolicitation(User $user, DateTimeImmutable $expiresAt): ResetPasswordSolicitation
    {
        return new ResetPasswordSolicitation(
            user: $user,
            expiresAt: $expiresAt,
            selector: str_repeat("s", 20),
            hashedToken: "hashed-token",
        );
    }
}
