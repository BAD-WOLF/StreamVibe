<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Authentication\UseCase\ResetPassword\Confirm;

use App\Application\Authentication\DTO\ResetPassword\Confirm\Request\ConfirmPasswordResetRequest;
use App\Application\Authentication\Port\PasswordResetInterface;
use App\Application\Authentication\Port\RefreshTokenRevokerInterface;
use App\Application\Authentication\Port\ValidatedPasswordResetToken;
use App\Application\Authentication\UseCase\ResetPassword\Confirm\ConfirmPasswordResetUseCase;
use App\Application\Shared\RequestValidator;
use App\Domain\Exception\ExpiredResetTokenException;
use App\Domain\Exception\InvalidResetTokenException;
use App\Domain\Exception\UserValidationException;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

/**
 * ConfirmPasswordResetUseCase (POST /reset-password/confirm): a ordem dos
 * passos e o que NÃO pode acontecer quando o token não vale.
 *
 * O validador é um dublê: as regras de senha (NewPassword) incluem
 * NotCompromisedPassword, que consulta a API do haveibeenpwned — elas são
 * exercitadas no ResetPasswordApiTest, com a consulta desligada em
 * config/packages/validator.yaml (when@test).
 *
 * NOTA (out/2026): criado com o endpoint de troca de senha, que não
 * existia.
 */
final class ConfirmPasswordResetUseCaseTest extends TestCase
{
    private const string TOKEN = "tokentokentokentokentokentokentokentoken";

    private const string NEW_PASSWORD = "Correct-Horse-Battery-Staple-42";

    /**
     * O token é invalidado antes de a senha ser gravada, e os refresh
     * tokens só depois: se a gravação falhar, o link já não serve mais
     * (o usuário pede outro), e ninguém fica com sessão renovável da
     * senha antiga depois da troca.
     */
    public function testChangesThePasswordInvalidatesTheLinkAndRevokesRefreshTokens(): void
    {
        $user = $this->makeUser();
        $calls = [];

        $passwordReset = $this->createMock(PasswordResetInterface::class);
        $passwordReset->expects($this->once())
            ->method("validateToken")
            ->with(self::TOKEN)
            ->willReturnCallback(static function () use ($user, &$calls): ValidatedPasswordResetToken {
                $calls[] = "validateToken";

                return new ValidatedPasswordResetToken($user, new DateTimeImmutable("+1 hour"));
            });
        $passwordReset->expects($this->once())
            ->method("removeToken")
            ->with(self::TOKEN)
            ->willReturnCallback(static function () use (&$calls): void {
                $calls[] = "removeToken";
            });

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->once())
            ->method("save")
            ->with($user, true)
            ->willReturnCallback(static function () use (&$calls): void {
                $calls[] = "save";
            });

        $refreshTokenRevoker = $this->createMock(RefreshTokenRevokerInterface::class);
        $refreshTokenRevoker->expects($this->once())
            ->method("revokeAllFor")
            ->with($user)
            ->willReturnCallback(static function () use (&$calls): int {
                $calls[] = "revokeAllFor";

                return 2;
            });

        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $passwordHasher->method("hashPassword")
            ->willReturnCallback(static fn(User $hashedUser, string $plainPassword): string => "hashed:" . $plainPassword);

        $response = $this->makeUseCase(
            $userRepository,
            $passwordReset,
            $refreshTokenRevoker,
            $this->validatorWithViolations(),
            $passwordHasher,
        )->execute(new ConfirmPasswordResetRequest(token: self::TOKEN, password: self::NEW_PASSWORD));

        self::assertTrue($response->passwordReset);
        self::assertSame("Your password has been reset successfully.", $response->message);
        self::assertSame("hashed:" . self::NEW_PASSWORD, $user->getPassword());
        self::assertSame(["validateToken", "removeToken", "save", "revokeAllFor"], $calls);
    }

    /**
     * @return iterable<string, array{\Throwable}>
     */
    public static function rejectedTokenProvider(): iterable
    {
        yield "invalid token (400)" => [InvalidResetTokenException::fromReason("The reset password link is invalid.")];
        yield "expired token (410)" => [ExpiredResetTokenException::tokenExpired(self::TOKEN)];
    }

    #[DataProvider("rejectedTokenProvider")]
    public function testRejectedTokenChangesNothing(Throwable $tokenError): void
    {
        $passwordReset = $this->createMock(PasswordResetInterface::class);
        $passwordReset->method("validateToken")->willThrowException($tokenError);
        $passwordReset->expects($this->never())->method("removeToken");

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->never())->method("save");

        $refreshTokenRevoker = $this->createMock(RefreshTokenRevokerInterface::class);
        $refreshTokenRevoker->expects($this->never())->method("revokeAllFor");

        $useCase = $this->makeUseCase(
            $userRepository,
            $passwordReset,
            $refreshTokenRevoker,
            $this->validatorWithViolations(),
            $this->createStub(UserPasswordHasherInterface::class),
        );

        $this->expectExceptionObject($tokenError);

        $useCase->execute(new ConfirmPasswordResetRequest(token: self::TOKEN, password: self::NEW_PASSWORD));
    }

    public function testInvalidPasswordIsRejectedBeforeTheTokenIsChecked(): void
    {
        $passwordReset = $this->createMock(PasswordResetInterface::class);
        $passwordReset->expects($this->never())->method("validateToken");

        $useCase = $this->makeUseCase(
            $this->createStub(UserRepositoryInterface::class),
            $passwordReset,
            $this->createStub(RefreshTokenRevokerInterface::class),
            $this->validatorWithViolations(password: "Password must be at least 12 characters long"),
            $this->createStub(UserPasswordHasherInterface::class),
        );

        try {
            $useCase->execute(new ConfirmPasswordResetRequest(token: self::TOKEN, password: "short"));
            self::fail("A password that breaks the rules should be rejected");
        } catch (UserValidationException $exception) {
            self::assertSame(400, $exception->getCode());
            self::assertSame(["Password: Password must be at least 12 characters long"], $exception->getErrors());
        }
    }

    private function makeUseCase(
        UserRepositoryInterface $userRepository,
        PasswordResetInterface $passwordReset,
        RefreshTokenRevokerInterface $refreshTokenRevoker,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher,
    ): ConfirmPasswordResetUseCase {
        return new ConfirmPasswordResetUseCase(
            userRepository: $userRepository,
            passwordReset: $passwordReset,
            refreshTokenRevoker: $refreshTokenRevoker,
            passwordHasher: $passwordHasher,
            requestValidator: new RequestValidator($validator, new NullLogger()),
            logger: new NullLogger(),
            translator: new IdentityTranslator(),
        );
    }

    /**
     * Validador que devolve as violações dadas, como argumentos nomeados
     * (campo: mensagem). Sem argumentos, nenhuma violação.
     */
    private function validatorWithViolations(string ...$messagesByField): ValidatorInterface
    {
        $violations = new ConstraintViolationList();

        foreach ($messagesByField as $field => $message) {
            $violations->add(new ConstraintViolation($message, null, [], null, $field, null));
        }

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method("validate")->willReturn($violations);

        return $validator;
    }

    private function makeUser(): User
    {
        $user = new User();
        $user->setEmail("user@example.com");
        $user->setPassword("hashed:old-password");
        $user->setIsVerified(true);
        $user->id = 42;

        return $user;
    }
}
