<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Security;

use App\Infrastructure\Security\LoginAttemptLimiter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Clock\MockClock;

/**
 * LoginAttemptLimiter: as duas contagens (IP + email e só IP), a janela
 * fixa e o que o login certo zera.
 *
 * NOTA (nov/2026): criado com o limite de tentativas de login.
 */
final class LoginAttemptLimiterTest extends TestCase
{
    private const string IP = '203.0.113.10';

    private ArrayAdapter $cache;

    private MockClock $clock;

    private LoginAttemptLimiter $limiter;

    protected function setUp(): void
    {
        $this->cache = new ArrayAdapter();
        $this->clock = new MockClock();
        $this->limiter = new LoginAttemptLimiter($this->cache, $this->clock);
    }

    public function testAnAccountIsBlockedForTheIpAfterTheMaximumOfFailures(): void
    {
        for ($i = 1; $i < LoginAttemptLimiter::MAX_FAILURES_PER_ACCOUNT; $i++) {
            $this->limiter->recordFailure(self::IP, 'ana@example.com');
            self::assertSame(0, $this->limiter->secondsUntilAllowed(self::IP, 'ana@example.com'), "blocked after {$i} failures");
        }

        $this->limiter->recordFailure(self::IP, 'ana@example.com');

        self::assertSame(
            LoginAttemptLimiter::WINDOW_SECONDS,
            $this->limiter->secondsUntilAllowed(self::IP, 'ana@example.com'),
        );
    }

    /**
     * A janela começa na primeira falha e não anda com as seguintes.
     */
    public function testTheWindowStartsAtTheFirstFailure(): void
    {
        $this->limiter->recordFailure(self::IP, 'ana@example.com');
        $this->clock->sleep(600);

        for ($i = 1; $i < LoginAttemptLimiter::MAX_FAILURES_PER_ACCOUNT; $i++) {
            $this->limiter->recordFailure(self::IP, 'ana@example.com');
        }

        self::assertSame(
            LoginAttemptLimiter::WINDOW_SECONDS - 600,
            $this->limiter->secondsUntilAllowed(self::IP, 'ana@example.com'),
        );

        $this->clock->sleep(LoginAttemptLimiter::WINDOW_SECONDS - 600);

        self::assertSame(0, $this->limiter->secondsUntilAllowed(self::IP, 'ana@example.com'));
    }

    public function testTheEmailIsComparedWithoutCaseAndSurroundingSpaces(): void
    {
        for ($i = 0; $i < LoginAttemptLimiter::MAX_FAILURES_PER_ACCOUNT; $i++) {
            $this->limiter->recordFailure(self::IP, $i % 2 === 0 ? 'Ana@Example.com' : ' ana@example.com ');
        }

        self::assertGreaterThan(0, $this->limiter->secondsUntilAllowed(self::IP, 'ana@example.com'));
    }

    public function testAnotherIpAndAnotherAccountAreNotBlocked(): void
    {
        for ($i = 0; $i < LoginAttemptLimiter::MAX_FAILURES_PER_ACCOUNT; $i++) {
            $this->limiter->recordFailure(self::IP, 'ana@example.com');
        }

        self::assertSame(0, $this->limiter->secondsUntilAllowed('198.51.100.7', 'ana@example.com'));
        self::assertSame(0, $this->limiter->secondsUntilAllowed(self::IP, 'bia@example.com'));
    }

    /**
     * Credential stuffing: uma tentativa em cada conta, todas do mesmo IP.
     */
    public function testTheIpIsBlockedForEveryAccountAfterItsMaximumOfFailures(): void
    {
        for ($i = 0; $i < LoginAttemptLimiter::MAX_FAILURES_PER_IP; $i++) {
            $this->limiter->recordFailure(self::IP, "user{$i}@example.com");
        }

        self::assertSame(
            LoginAttemptLimiter::WINDOW_SECONDS,
            $this->limiter->secondsUntilAllowed(self::IP, 'someone-else@example.com'),
        );
        self::assertSame(0, $this->limiter->secondsUntilAllowed('198.51.100.7', 'someone-else@example.com'));
    }

    public function testAFailureWithoutEmailCountsOnlyForTheIp(): void
    {
        for ($i = 0; $i < LoginAttemptLimiter::MAX_FAILURES_PER_IP; $i++) {
            $this->limiter->recordFailure(self::IP, null);
        }

        self::assertGreaterThan(0, $this->limiter->secondsUntilAllowed(self::IP, null));
        self::assertGreaterThan(0, $this->limiter->secondsUntilAllowed(self::IP, 'ana@example.com'));
    }

    /**
     * O login certo zera a contagem da conta, mas não a do IP: quem tem uma
     * conta válida não "limpa" o IP entre uma rodada de tentativas e outra.
     */
    public function testASuccessfulLoginResetsOnlyTheAccountCounter(): void
    {
        for ($i = 0; $i < LoginAttemptLimiter::MAX_FAILURES_PER_ACCOUNT; $i++) {
            $this->limiter->recordFailure(self::IP, 'ana@example.com');
        }
        for ($i = LoginAttemptLimiter::MAX_FAILURES_PER_ACCOUNT; $i < LoginAttemptLimiter::MAX_FAILURES_PER_IP; $i++) {
            $this->limiter->recordFailure(self::IP, "user{$i}@example.com");
        }

        $this->limiter->recordSuccess(self::IP, 'ana@example.com');

        // a conta foi zerada, mas o IP continua no limite
        self::assertGreaterThan(0, $this->limiter->secondsUntilAllowed(self::IP, 'ana@example.com'));
        self::assertGreaterThan(0, $this->limiter->secondsUntilAllowed(self::IP, null));
    }

    public function testASuccessfulLoginUnblocksTheAccountWhenTheIpIsBelowItsLimit(): void
    {
        for ($i = 0; $i < LoginAttemptLimiter::MAX_FAILURES_PER_ACCOUNT; $i++) {
            $this->limiter->recordFailure(self::IP, 'ana@example.com');
        }

        $this->limiter->recordSuccess(self::IP, 'ana@example.com');

        self::assertSame(0, $this->limiter->secondsUntilAllowed(self::IP, 'ana@example.com'));
    }

    /**
     * Um valor estranho na chave (outro formato, cache corrompido) conta
     * como "sem falhas", em vez de quebrar o login.
     */
    public function testAnUnexpectedCachedValueIsIgnored(): void
    {
        for ($i = 0; $i < LoginAttemptLimiter::MAX_FAILURES_PER_ACCOUNT; $i++) {
            $this->limiter->recordFailure(self::IP, 'ana@example.com');
        }

        foreach (array_keys($this->cache->getValues()) as $key) {
            $item = $this->cache->getItem($key);
            $item->set('garbage');
            $this->cache->save($item);
        }

        self::assertSame(0, $this->limiter->secondsUntilAllowed(self::IP, 'ana@example.com'));

        $this->limiter->recordFailure(self::IP, 'ana@example.com');

        self::assertSame(0, $this->limiter->secondsUntilAllowed(self::IP, 'ana@example.com'));
    }

    /**
     * Nem email nem IP em claro nas chaves do cache.
     */
    public function testTheCacheKeysDoNotContainTheEmailOrTheIp(): void
    {
        $this->limiter->recordFailure(self::IP, 'ana@example.com');

        $keys = array_keys($this->cache->getValues());

        self::assertCount(2, $keys);
        foreach ($keys as $key) {
            self::assertStringNotContainsString('ana', $key);
            self::assertStringNotContainsString(self::IP, $key);
        }
    }
}
