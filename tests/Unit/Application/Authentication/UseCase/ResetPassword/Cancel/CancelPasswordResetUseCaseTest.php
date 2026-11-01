<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Authentication\UseCase\ResetPassword\Cancel;

use App\Application\Authentication\DTO\ResetPassword\Cancel\Request\CancelPasswordResetRequest;
use App\Application\Authentication\Port\PasswordResetInterface;
use App\Application\Authentication\Port\ValidatedPasswordResetToken;
use App\Application\Authentication\UseCase\ResetPassword\Cancel\CancelPasswordResetUseCase;
use App\Application\Shared\RequestValidator;
use App\Domain\Exception\ExpiredResetTokenException;
use App\Domain\Exception\InvalidResetTokenException;
use App\Domain\Exception\ResetTokenNotFoundException;
use App\Domain\Exception\UserValidationException;
use App\Domain\User\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\Validation;
use Throwable;

/**
 * CancelPasswordResetUseCase (POST /reset-password/cancel): só um token
 * que ainda vale é cancelado; inválido, inexistente ou vencido é 404
 * ("não há pedido válido para cancelar"), sem apagar nada.
 *
 * NOTA (out/2026): substitui a parte de cancel() do antigo
 * ResetUserPasswordUseCaseTest, que não tinha nenhum teste.
 */
final class CancelPasswordResetUseCaseTest extends TestCase
{
    private const string TOKEN = "tokentokentokentokentokentokentokentoken";

    public function testValidTokenIsCancelled(): void
    {
        $user = new User();
        $user->setEmail("user@example.com");
        $user->id = 42;

        $passwordReset = $this->createMock(PasswordResetInterface::class);
        $passwordReset->method("validateToken")
            ->willReturn(new ValidatedPasswordResetToken($user, new DateTimeImmutable("+1 hour")));
        $passwordReset->expects($this->once())->method("removeToken")->with(self::TOKEN);

        $response = $this->makeUseCase($passwordReset)
            ->execute(new CancelPasswordResetRequest(token: self::TOKEN));

        self::assertTrue($response->cancelled);
        self::assertSame("Password reset request has been cancelled.", $response->message);
    }

    /**
     * @return iterable<string, array{\Throwable}>
     */
    public static function rejectedTokenProvider(): iterable
    {
        yield "invalid or unknown token" => [InvalidResetTokenException::fromReason("The reset password link is invalid.")];
        yield "expired token" => [ExpiredResetTokenException::tokenExpired(self::TOKEN)];
    }

    #[DataProvider("rejectedTokenProvider")]
    public function testTokenThatNoLongerWorksIsNotFound(Throwable $tokenError): void
    {
        $passwordReset = $this->createMock(PasswordResetInterface::class);
        $passwordReset->method("validateToken")->willThrowException($tokenError);
        $passwordReset->expects($this->never())->method("removeToken");

        $useCase = $this->makeUseCase($passwordReset);

        $this->expectException(ResetTokenNotFoundException::class);
        $this->expectExceptionCode(404);

        $useCase->execute(new CancelPasswordResetRequest(token: self::TOKEN));
    }

    public function testBlankTokenIsRejected(): void
    {
        $passwordReset = $this->createMock(PasswordResetInterface::class);
        $passwordReset->expects($this->never())->method("validateToken");

        $useCase = $this->makeUseCase($passwordReset);

        $this->expectException(UserValidationException::class);
        $this->expectExceptionCode(400);

        $useCase->execute(new CancelPasswordResetRequest(token: ""));
    }

    private function makeUseCase(PasswordResetInterface $passwordReset): CancelPasswordResetUseCase
    {
        return new CancelPasswordResetUseCase(
            passwordReset: $passwordReset,
            requestValidator: new RequestValidator(
                Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
                new NullLogger(),
            ),
            logger: new NullLogger(),
            translator: new IdentityTranslator(),
        );
    }
}
