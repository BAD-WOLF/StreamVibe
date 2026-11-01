<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Authentication\UseCase\ResendVerification;

use App\Application\Authentication\DTO\ResendVerification\Request\ResendVerificationEmailRequest;
use App\Application\Authentication\Port\EmailVerificationInterface;
use App\Application\Authentication\UseCase\ResendVerification\ResendVerificationEmailUseCase;
use App\Application\Shared\RequestValidator;
use App\Domain\Exception\UserAlreadyExistsException;
use App\Domain\Exception\UserValidationException;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\Validation;

/**
 * ResendVerificationEmailUseCase (POST /resend-verification).
 *
 * NOTA (out/2026): criado quando o reenvio virou UseCase próprio (antes
 * era um "modo" do RegisterUserUseCase ligado por flag, sem teste).
 */
final class ResendVerificationEmailUseCaseTest extends TestCase
{
    public function testUnverifiedAccountGetsTheEmailAgain(): void
    {
        $user = $this->makeUser(verified: false);

        $emailVerification = $this->createMock(EmailVerificationInterface::class);
        $emailVerification->expects($this->once())
            ->method("sendConfirmationEmail")
            ->with($user, true);

        $response = $this->makeUseCase($this->userRepositoryReturning($user), $emailVerification)
            ->execute(new ResendVerificationEmailRequest(email: "user@example.com"));

        self::assertTrue($response->emailSent);
        self::assertSame("Verification email has been sent. Please check your inbox.", $response->message);
    }

    public function testUnknownEmailAnswersGenericMessageWithoutSending(): void
    {
        $emailVerification = $this->createMock(EmailVerificationInterface::class);
        $emailVerification->expects($this->never())->method("sendConfirmationEmail");

        $response = $this->makeUseCase($this->userRepositoryReturning(null), $emailVerification)
            ->execute(new ResendVerificationEmailRequest(email: "ghost@example.com"));

        self::assertFalse($response->emailSent);
        self::assertSame(
            "If an account with this email exists and is not verified, a verification email has been sent.",
            $response->message,
        );
    }

    public function testVerifiedAccountIsAConflict(): void
    {
        $emailVerification = $this->createMock(EmailVerificationInterface::class);
        $emailVerification->expects($this->never())->method("sendConfirmationEmail");

        $useCase = $this->makeUseCase(
            $this->userRepositoryReturning($this->makeUser(verified: true)),
            $emailVerification,
        );

        $this->expectException(UserAlreadyExistsException::class);
        $this->expectExceptionCode(409);
        $this->expectExceptionMessage("This account is already verified");

        $useCase->execute(new ResendVerificationEmailRequest(email: "user@example.com"));
    }

    public function testInvalidEmailIsRejectedBeforeAnyLookup(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->never())->method("findByEmail");

        $useCase = $this->makeUseCase($userRepository, $this->createStub(EmailVerificationInterface::class));

        $this->expectException(UserValidationException::class);
        $this->expectExceptionCode(400);

        $useCase->execute(new ResendVerificationEmailRequest(email: "not-an-email"));
    }

    private function makeUseCase(
        UserRepositoryInterface $userRepository,
        EmailVerificationInterface $emailVerification,
    ): ResendVerificationEmailUseCase {
        return new ResendVerificationEmailUseCase(
            userRepository: $userRepository,
            emailVerification: $emailVerification,
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

    private function makeUser(bool $verified): User
    {
        $user = new User();
        $user->setEmail("user@example.com");
        $user->setPassword("hashed-password");
        $user->setIsVerified($verified);
        $user->id = 42;

        return $user;
    }
}
