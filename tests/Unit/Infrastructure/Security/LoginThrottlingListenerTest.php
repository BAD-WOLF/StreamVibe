<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Security;

use App\Infrastructure\Security\LoginAttemptLimiter;
use App\Infrastructure\Security\LoginThrottlingListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Translation\IdentityTranslator;

/**
 * LoginThrottlingListener: recusa antes da senha, conta as falhas, zera no
 * login certo e responde 429 com Retry-After.
 *
 * NOTA (nov/2026): criado com o limite de tentativas de login.
 */
final class LoginThrottlingListenerTest extends TestCase
{
    private const string IP = '203.0.113.10';

    private const string EMAIL = 'ana@example.com';

    private MockClock $clock;

    private LoginAttemptLimiter $limiter;

    private RequestStack $requestStack;

    private LoginThrottlingListener $listener;

    protected function setUp(): void
    {
        $this->clock = new MockClock();
        $this->limiter = new LoginAttemptLimiter(new ArrayAdapter(), $this->clock);
        $this->requestStack = new RequestStack();
        $this->requestStack->push($this->request());
        $this->listener = new LoginThrottlingListener($this->limiter, $this->requestStack, new IdentityTranslator());
    }

    public function testAnAttemptBelowTheLimitPasses(): void
    {
        $this->failLogins(LoginAttemptLimiter::MAX_FAILURES_PER_ACCOUNT - 1);

        $this->listener->onCheckPassport($this->checkPassportEvent());

        $this->addToAssertionCount(1);
    }

    public function testTheAttemptAfterTheLimitIsRefusedBeforeThePassword(): void
    {
        $this->failLogins(LoginAttemptLimiter::MAX_FAILURES_PER_ACCOUNT);

        try {
            $this->listener->onCheckPassport($this->checkPassportEvent());
            self::fail('The attempt over the limit should be refused');
        } catch (TooManyLoginAttemptsAuthenticationException $exception) {
            self::assertSame(['%minutes%' => 15, '%count%' => 15], $exception->getMessageData());
        }
    }

    public function testTheRefusalIsA429WithRetryAfter(): void
    {
        $this->failLogins(LoginAttemptLimiter::MAX_FAILURES_PER_ACCOUNT);
        $this->clock->sleep(60);

        $event = $this->loginFailureEvent(new TooManyLoginAttemptsAuthenticationException(14));
        $this->listener->onLoginFailure($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
        self::assertSame((string) (LoginAttemptLimiter::WINDOW_SECONDS - 60), $response->headers->get('Retry-After'));
        self::assertSame(
            ['code' => 429, 'message' => 'Too many failed login attempts, please try again in 14 minutes.'],
            json_decode((string) $response->getContent(), true),
        );
    }

    /**
     * A recusa não é mais uma falha: com uma a menos que o limite, a conta
     * continua liberada depois dela.
     */
    public function testTheRefusalIsNotCountedAsAnotherFailure(): void
    {
        $this->failLogins(LoginAttemptLimiter::MAX_FAILURES_PER_ACCOUNT - 1);

        $this->listener->onLoginFailure($this->loginFailureEvent(new TooManyLoginAttemptsAuthenticationException(1)));

        self::assertSame(0, $this->limiter->secondsUntilAllowed(self::IP, self::EMAIL));
    }

    public function testAWrongPasswordIsCountedAndKeepsTheResponse(): void
    {
        $original = new JsonResponse(['code' => 401, 'message' => 'Invalid credentials.'], 401);
        $event = $this->loginFailureEvent(new BadCredentialsException(), $original);

        $this->listener->onLoginFailure($event);

        self::assertSame($original, $event->getResponse());

        $this->failLogins(LoginAttemptLimiter::MAX_FAILURES_PER_ACCOUNT - 1);
        self::assertGreaterThan(0, $this->limiter->secondsUntilAllowed(self::IP, self::EMAIL));
    }

    public function testASuccessfulLoginResetsTheAccount(): void
    {
        $this->failLogins(LoginAttemptLimiter::MAX_FAILURES_PER_ACCOUNT);

        $this->listener->onLoginSuccess(new LoginSuccessEvent(
            $this->createStub(AuthenticatorInterface::class),
            $this->passport(),
            $this->createStub(TokenInterface::class),
            $this->request(),
            null,
            'login',
        ));

        self::assertSame(0, $this->limiter->secondsUntilAllowed(self::IP, self::EMAIL));
    }

    /**
     * O mesmo email de outro IP não é afetado.
     */
    public function testTheBlockIsPerIp(): void
    {
        $this->failLogins(LoginAttemptLimiter::MAX_FAILURES_PER_ACCOUNT);

        $requestStack = new RequestStack();
        $requestStack->push($this->request('198.51.100.7'));
        $listener = new LoginThrottlingListener($this->limiter, $requestStack, new IdentityTranslator());

        $listener->onCheckPassport($this->checkPassportEvent());

        $this->addToAssertionCount(1);
    }

    public function testWithoutARequestNothingIsChecked(): void
    {
        $this->failLogins(LoginAttemptLimiter::MAX_FAILURES_PER_ACCOUNT);

        $listener = new LoginThrottlingListener($this->limiter, new RequestStack(), new IdentityTranslator());
        $listener->onCheckPassport($this->checkPassportEvent());

        $this->addToAssertionCount(1);
    }

    /**
     * Registra $count logins com a senha errada pelo listener.
     */
    private function failLogins(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->listener->onLoginFailure($this->loginFailureEvent(new BadCredentialsException()));
        }
    }

    private function checkPassportEvent(): CheckPassportEvent
    {
        return new CheckPassportEvent($this->createStub(AuthenticatorInterface::class), $this->passport());
    }

    private function loginFailureEvent(
        \Symfony\Component\Security\Core\Exception\AuthenticationException $exception,
        ?Response $response = null,
    ): LoginFailureEvent {
        return new LoginFailureEvent(
            $exception,
            $this->createStub(AuthenticatorInterface::class),
            $this->request(),
            $response,
            'login',
            $this->passport(),
        );
    }

    private function passport(): Passport
    {
        return new Passport(new UserBadge(self::EMAIL), new PasswordCredentials('wrong-password'));
    }

    private function request(string $ip = self::IP): Request
    {
        return Request::create('/api/login_check', 'POST', server: ['REMOTE_ADDR' => $ip]);
    }
}
