<?php

declare(strict_types=1);

namespace App\Tests\Application\Api\Authentication;

use App\Infrastructure\Security\LoginAttemptLimiter;
use App\Tests\Helper\ApiRequestTrait;
use App\Tests\Helper\DatabaseTestTrait;
use App\Tests\Helper\UserTestBuilder;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Limite de tentativas no POST /api/login_check (LoginThrottlingListener):
 * depois de LoginAttemptLimiter::MAX_FAILURES_PER_ACCOUNT senhas erradas,
 * aquele IP não tenta mais aquela conta — nem com a senha certa — e recebe
 * 429 com Retry-After.
 *
 * A contagem fica no cache (pool cache.login_throttling, em arquivo), que
 * sobrevive entre um teste e outro, e entre uma execução e outra. Por isso
 * cada teste usa um IP (REMOTE_ADDR) e um email que não se repetem.
 *
 * NOTA (nov/2026): criado com o limite de tentativas de login.
 */
final class LoginThrottlingApiTest extends WebTestCase
{
    use DatabaseTestTrait;
    use ApiRequestTrait;

    private const string PASSWORD = 'SecurePassword123!';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->setupTestDatabase();
    }

    protected function tearDown(): void
    {
        $this->teardownTestDatabase();
        parent::tearDown();
    }

    public function testAfterTheMaximumOfWrongPasswordsEvenTheRightOneIsRefused(): void
    {
        $email = $this->makeUser();
        $ip = $this->uniqueIp();

        for ($i = 0; $i < LoginAttemptLimiter::MAX_FAILURES_PER_ACCOUNT; $i++) {
            $this->login($email, 'WrongPassword123!', $ip);
            $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        }

        $this->login($email, self::PASSWORD, $ip);

        $this->assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
        $retryAfter = (int) $this->client->getResponse()->headers->get('Retry-After');
        $this->assertGreaterThan(0, $retryAfter);
        $this->assertLessThanOrEqual(LoginAttemptLimiter::WINDOW_SECONDS, $retryAfter);

        $data = $this->responseData($this->client);
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $data['code']);
        $this->assertStringContainsString('Too many failed login attempts', (string) $data['message']);
    }

    /**
     * O bloqueio é do par IP + conta: de outro IP, o dono entra.
     */
    public function testTheOwnerStillLogsInFromAnotherIp(): void
    {
        $email = $this->makeUser();
        $ip = $this->uniqueIp();

        for ($i = 0; $i < LoginAttemptLimiter::MAX_FAILURES_PER_ACCOUNT; $i++) {
            $this->login($email, 'WrongPassword123!', $ip);
        }

        $this->login($email, self::PASSWORD, $this->uniqueIp());

        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('token', $this->responseData($this->client));
    }

    /**
     * O login certo zera a contagem da conta: as falhas antes dele não
     * somam com as depois.
     */
    public function testASuccessfulLoginResetsTheCount(): void
    {
        $email = $this->makeUser();
        $ip = $this->uniqueIp();

        for ($i = 1; $i < LoginAttemptLimiter::MAX_FAILURES_PER_ACCOUNT; $i++) {
            $this->login($email, 'WrongPassword123!', $ip);
        }

        $this->login($email, self::PASSWORD, $ip);
        $this->assertResponseIsSuccessful();

        for ($i = 1; $i < LoginAttemptLimiter::MAX_FAILURES_PER_ACCOUNT; $i++) {
            $this->login($email, 'WrongPassword123!', $ip);
        }

        $this->login($email, self::PASSWORD, $ip);
        $this->assertResponseIsSuccessful();
    }

    /**
     * Um usuário que não existe conta igual a uma senha errada: a resposta
     * é a mesma, e quem testa emails também é limitado.
     */
    public function testUnknownAccountsAreLimitedToo(): void
    {
        $email = 'nobody-' . bin2hex(random_bytes(6)) . '@example.com';
        $ip = $this->uniqueIp();

        for ($i = 0; $i < LoginAttemptLimiter::MAX_FAILURES_PER_ACCOUNT; $i++) {
            $this->login($email, 'WrongPassword123!', $ip);
            $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        }

        $this->login($email, 'WrongPassword123!', $ip);

        $this->assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
    }

    /**
     * Uma conta verificada, com email e senha conhecidos; o email não se
     * repete entre execuções (a contagem fica no cache).
     */
    private function makeUser(): string
    {
        $email = 'throttle-' . bin2hex(random_bytes(6)) . '@example.com';

        $user = UserTestBuilder::create()
            ->withEmail($email)
            ->withPassword('placeholder')
            ->verified()
            ->build();

        $user->setPassword(
            static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, self::PASSWORD),
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $email;
    }

    private function login(string $email, string $password, string $ip): void
    {
        $this->client->request(
            'POST',
            '/api/login_check',
            server: ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => $ip],
            content: json_encode(['email' => $email, 'password' => $password], JSON_THROW_ON_ERROR),
        );
    }

    /**
     * Um IPv6 aleatório da faixa de documentação (2001:db8::/32): 96 bits
     * sorteados, então não se repete entre execuções.
     */
    private function uniqueIp(): string
    {
        return '2001:db8:' . implode(':', str_split(bin2hex(random_bytes(12)), 4));
    }
}
