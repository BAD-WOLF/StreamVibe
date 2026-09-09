<?php

declare(strict_types = 1);

namespace App\Tests\Unit\Application\Authentication;

use App\Application\Authentication\DTO\Reset\Request\ResetPasswordRequest;
use App\Application\Authentication\DTO\Reset\Response\ResetPasswordResponse;
use App\Application\Authentication\DTO\Reset\Response\ResetPasswordStatusResponse;
use App\Application\Authentication\UseCase\ResetUserPasswordUseCase;
use App\Domain\Authentication\Entity\ResetPasswordSolicitation;
use App\Domain\Authentication\Repository\ResetPasswordSolicitationRepositoryInterface;
use App\Domain\Exception\ExpiredResetTokenException;
use App\Domain\Exception\InvalidResetTokenException;
use App\Domain\Exception\ResetTokenNotFoundException;
use App\Domain\Exception\UserNotVerifiedException;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ExpiredResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\Exception\InvalidResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\Exception\TooManyPasswordRequestsException;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * Cobre os pontos que dependiam de Domain\Authentication\Exception::* e
 * agora dependem de Domain\Exception::* (migração feita para alinhar com
 * o ExceptionListener, que só reconhece a árvore Domain\Exception).
 */
final class ResetUserPasswordUseCaseTest extends TestCase {
    private UserRepositoryInterface $userRepository;
    private ResetPasswordSolicitationRepositoryInterface $resetRepository;
    private ResetPasswordHelperInterface $resetHelper;
    private ValidatorInterface $validator;
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private TranslatorInterface $translator;

    protected function setUp(): void {
        // NOTA: trocado de createMock() para createStub(). Nenhum destes 7
        // dublês tem expects() configurado em lugar nenhum do arquivo — são
        // usados só como fonte de valor de retorno (->method()->willReturn()),
        // nunca para verificar se foram chamados. createMock() sem
        // expects() é exatamente o code smell que o PHPUnit 12 passou a
        // sinalizar via Notice ("No expectations were configured for the
        // mock object... Consider refactoring your test code to use a test
        // stub instead"). createStub() é semanticamente correto aqui e
        // elimina a notice.
        $this->userRepository = $this->createStub(UserRepositoryInterface::class);
        $this->resetRepository = $this->createStub(ResetPasswordSolicitationRepositoryInterface::class);
        $this->resetHelper = $this->createStub(ResetPasswordHelperInterface::class);
        $this->validator = $this->createStub(ValidatorInterface::class);
        $this->mailer = $this->createStub(MailerInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);
        $this->translator = $this->createStub(TranslatorInterface::class);

        // Passthrough: devolve o próprio id da tradução, suficiente pros asserts de status/exception.
        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $id, array $parameters = []): string => $id);

        // Nenhum teste aqui exercita violação de validação do próprio request.
        $this->validator->method('validate')->willReturn(new ConstraintViolationList());
    }

    private function makeUseCase(): ResetUserPasswordUseCase {
        return new ResetUserPasswordUseCase(
            userRepository: $this->userRepository,
            resetPasswordRequestRepository: $this->resetRepository,
            resetPasswordHelper: $this->resetHelper,
            validator: $this->validator,
            mailer: $this->mailer,
            logger: $this->logger,
            translator: $this->translator,
        );
    }

    private function makeUser(int $id, string $email, bool $verified = true): User {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('hashed-password');
        $user->setIsVerified($verified);

        $idProperty = new ReflectionProperty(User::class, 'id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($user, $id);

        return $user;
    }

    private function makeRequest(string $email = 'user@example.com'): ResetPasswordRequest {
        return new ResetPasswordRequest(email: $email);
    }

    /**
     * Instância real (não mock) de ResetPasswordSolicitation.
     *
     * Usar createMock() aqui quebra no PHPUnit 12 + PHP 8.4: o gerador de
     * dublês tenta declarar "implements DateTimeInterface" na classe mock
     * por causa dos métodos do ResetPasswordRequestTrait que retornam
     * DateTimeInterface, e isso é proibido para classes de usuário
     * (fatal error, derruba o processo inteiro). Como a entidade é simples
     * de construir, instanciamos de verdade.
     */
    private function makeSolicitation(
        User $user,
        DateTimeInterface $expiresAt,
        string $selector = 'selector1234567890',
        string $hashedToken = 'hashedtoken1234567890abcdef',
    ): ResetPasswordSolicitation {
        return new ResetPasswordSolicitation(
            user: $user,
            expiresAt: $expiresAt,
            selector: $selector,
            hashedToken: $hashedToken,
        );
    }

    // ------------------------------------------------------------------
    // execute()
    // ------------------------------------------------------------------

    public function testExecuteThrowsUserNotVerifiedExceptionWithCorrectHttpCode(): void {
        $user = $this->makeUser(id: 1, email: 'unverified@example.com', verified: false);

        $this->userRepository->method('findByEmail')->willReturn($user);

        $this->expectException(UserNotVerifiedException::class);

        try {
            $this->makeUseCase()->execute($this->makeRequest(email: 'unverified@example.com'));
        } catch (UserNotVerifiedException $e) {
            self::assertSame(403, $e->getCode());
            self::assertSame('user_not_verified_error', $e->getErrorType());
            throw $e;
        }
    }

    /**
     * NOTA: no código atual, o bloco "count($activeRequests) > 0" retorna
     * a resposta de "já existe solicitação ativa" ANTES de qualquer checagem
     * de "count >= 3" — ou seja, o branch que lança RateLimitExceededException
     * é inatingível nessa versão do UseCase (basta 1 request ativo pra
     * curto-circuitar). Este teste cobre o comportamento real: resposta de
     * sucesso reaproveitando os dados da solicitação já existente, sem
     * gerar novo token nem enviar novo email.
     */
    public function testExecuteReturnsExistingRequestResponseWhenActiveRequestFound(): void {
        $user = $this->makeUser(id: 2, email: 'hasactive@example.com', verified: true);
        $expiresAt = new DateTimeImmutable('+1 hour');

        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->resetRepository->method('removeExpiredRequests')->willReturn(0);
        $this->resetRepository->method('findActiveRequestsForUser')
            ->willReturn([$this->makeSolicitation($user, $expiresAt)]);

        $response = $this->makeUseCase()->execute($this->makeRequest(email: 'hasactive@example.com'));

        self::assertInstanceOf(ResetPasswordResponse::class, $response);
        self::assertFalse($response->wasEmailSent());
        self::assertNotNull($response->getExpiresAt());
        self::assertSame(
            $expiresAt->format('Y-m-d H:i:s'),
            $response->getExpiresAt()->format('Y-m-d H:i:s'),
        );
    }

    public function testExecuteReturnsGenericSuccessWhenUserDoesNotExist(): void {
        $this->userRepository->method('findByEmail')->willReturn(null);

        $response = $this->makeUseCase()->execute($this->makeRequest(email: 'ghost@example.com'));

        self::assertInstanceOf(ResetPasswordResponse::class, $response);
        self::assertFalse($response->wasEmailSent());
    }

    // ------------------------------------------------------------------
    // checkStatus()
    // ------------------------------------------------------------------

    public function testCheckStatusThrowsInvalidResetTokenExceptionWhenTokenIsEmpty(): void {
        $this->expectException(InvalidResetTokenException::class);

        try {
            $this->makeUseCase()->checkStatus('');
        } catch (InvalidResetTokenException $e) {
            self::assertSame(400, $e->getCode());
            self::assertSame('invalid_reset_token_error', $e->getErrorType());
            throw $e;
        }
    }

    public function testCheckStatusThrowsInvalidResetTokenExceptionWhenTokenIsTooShort(): void {
        $this->expectException(InvalidResetTokenException::class);

        try {
            $this->makeUseCase()->checkStatus('short-token');
        } catch (InvalidResetTokenException $e) {
            self::assertSame(400, $e->getCode());
            throw $e;
        }
    }

    /**
     * NOTA: removido o teste de "validateTokenAndFetchUser retorna null".
     * ResetPasswordHelperInterface declara o retorno como "object" (não
     * nullable) e documenta que falhas de validação são sinalizadas via
     * exceção (ResetPasswordExceptionInterface), não via null. O
     * "if (!$user)" dentro de checkStatus() é branch defensivo inatingível
     * pelo comportamento real do bundle — e o PHPUnit 12 corretamente recusa
     * um mock que violasse esse contrato de tipo (IncompatibleReturnValueException).
     * As exceções reais do bundle já estão cobertas pelos testes
     * "MapsBundle*" mais abaixo.
     */
    public function testCheckStatusThrowsResetTokenNotFoundExceptionWhenNoRequestForUser(): void {
        $token = str_repeat('b', 40);
        $user = $this->makeUser(id: 3, email: 'nosolicitation@example.com');

        $this->resetHelper->method('validateTokenAndFetchUser')->willReturn($user);
        $this->resetRepository->method('findByUser')->willReturn(null);

        $this->expectException(ResetTokenNotFoundException::class);

        try {
            $this->makeUseCase()->checkStatus($token);
        } catch (ResetTokenNotFoundException $e) {
            self::assertSame(404, $e->getCode());
            throw $e;
        }
    }

    public function testCheckStatusThrowsExpiredResetTokenExceptionWhenRequestIsExpired(): void {
        $token = str_repeat('c', 40);
        $user = $this->makeUser(id: 4, email: 'expired@example.com');

        $solicitation = $this->makeSolicitation($user, new DateTimeImmutable('-1 hour'));

        $this->resetHelper->method('validateTokenAndFetchUser')->willReturn($user);
        $this->resetRepository->method('findByUser')->willReturn($solicitation);

        $this->expectException(ExpiredResetTokenException::class);

        try {
            $this->makeUseCase()->checkStatus($token);
        } catch (ExpiredResetTokenException $e) {
            self::assertSame(410, $e->getCode());
            self::assertSame('expired_reset_token_error', $e->getErrorType());
            throw $e;
        }
    }

    public function testCheckStatusReturnsValidStatusForActiveToken(): void {
        $token = str_repeat('d', 40);
        $user = $this->makeUser(id: 5, email: 'active@example.com');

        $solicitation = $this->makeSolicitation($user, new DateTimeImmutable('+1 hour'));

        $this->resetHelper->method('validateTokenAndFetchUser')->willReturn($user);
        $this->resetRepository->method('findByUser')->willReturn($solicitation);

        $response = $this->makeUseCase()->checkStatus($token);

        self::assertInstanceOf(ResetPasswordStatusResponse::class, $response);
        self::assertTrue($response->isValid());
        self::assertFalse($response->isExpired());
    }

    public function testCheckStatusMapsBundleTooManyRequestsToInvalidResetTokenException(): void {
        $token = str_repeat('e', 40);

        $this->resetHelper->method('validateTokenAndFetchUser')
            ->willThrowException(new TooManyPasswordRequestsException(new DateTimeImmutable('+5 minutes')));

        $this->expectException(InvalidResetTokenException::class);

        try {
            $this->makeUseCase()->checkStatus($token);
        } catch (InvalidResetTokenException $e) {
            // Confirma que o método bundleError() novo (adicionado em Domain\Exception) responde certo.
            self::assertSame(400, $e->getCode());
            throw $e;
        }
    }

    public function testCheckStatusMapsBundleExpiredTokenToExpiredResetTokenException(): void {
        $token = str_repeat('f', 40);

        $this->resetHelper->method('validateTokenAndFetchUser')
            ->willThrowException(new ExpiredResetPasswordTokenException());

        $this->expectException(ExpiredResetTokenException::class);

        try {
            $this->makeUseCase()->checkStatus($token);
        } catch (ExpiredResetTokenException $e) {
            self::assertSame(410, $e->getCode());
            throw $e;
        }
    }

    public function testCheckStatusMapsUnexpectedErrorToInvalidResetTokenException(): void {
        $token = str_repeat('g', 40);

        $this->resetHelper->method('validateTokenAndFetchUser')
            ->willThrowException(new RuntimeException('boom - unexpected failure'));

        $this->expectException(InvalidResetTokenException::class);

        try {
            $this->makeUseCase()->checkStatus($token);
        } catch (InvalidResetTokenException $e) {
            // Confirma que unexpectedError() (novo, adicionado em Domain\Exception) responde certo.
            self::assertSame(400, $e->getCode());
            self::assertStringContainsString('boom - unexpected failure', $e->getMessage());
            throw $e;
        }
    }
}

