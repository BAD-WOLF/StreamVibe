<?php

declare(strict_types=1);

namespace App\Tests\Application\Api\Authentication;

use App\Domain\User\Entity\User;
use App\Tests\Helper\ApiAuthenticationTrait;
use App\Tests\Helper\ApiRequestTrait;
use App\Tests\Helper\DatabaseTestTrait;
use App\Tests\Helper\UserTestBuilder;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Uma conta bloqueada (administração de contas) não entra e não continua:
 * - o login com a senha certa é recusado (UserCheckerAuthLogin), e com a
 *   errada a resposta é a de sempre — sem a senha, ninguém descobre que a
 *   conta está bloqueada;
 * - o access token emitido antes do bloqueio para de valer na hora, e a
 *   renovação também é recusada (UserCheckerApi).
 *
 * E o login certo registra a data (LastLoginListener).
 *
 * Os logins que falham usam um IP próprio: contam no limite de tentativas
 * (LoginThrottlingApiTest).
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class BlockedAccountApiTest extends WebTestCase
{
    use DatabaseTestTrait;
    use ApiAuthenticationTrait;
    use ApiRequestTrait;

    private const string PASSWORD = 'SecurePassword123!';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->setServerParameter('HTTPS', 'on');
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->setupTestDatabase();
    }

    protected function tearDown(): void
    {
        $this->teardownTestDatabase();
        parent::tearDown();
    }

    public function testABlockedAccountCannotLogIn(): void
    {
        $this->block($this->makeUser('blocked@example.com'));
        $ip = $this->uniqueIp();

        $this->login('blocked@example.com', self::PASSWORD, $ip);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertSame('This account has been blocked.', $this->responseData($this->client)['message']);

        // a senha errada responde como para qualquer conta (o texto vem do
        // catálogo de segurança do Symfony, no idioma padrão)
        $this->login('blocked@example.com', 'WrongPassword123!', $ip);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertNotSame('This account has been blocked.', $this->responseData($this->client)['message']);
    }

    public function testAnAccessTokenFromBeforeTheBlockStopsWorking(): void
    {
        $user = $this->makeUser('ana@example.com');
        $this->authenticateClient($this->client, $user);

        $this->client->request('GET', '/pt_BR/api/me');
        $this->assertResponseIsSuccessful();

        $this->block($user);

        $this->client->request('GET', '/pt_BR/api/me');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Mesmo com o refresh token ainda no banco (o bloqueio pela API também
     * o apaga), a renovação não sai.
     */
    public function testTheSessionOfABlockedAccountIsNotRenewed(): void
    {
        $this->makeUser('ana@example.com');
        $this->login('ana@example.com', self::PASSWORD, $this->uniqueIp());
        $this->assertResponseIsSuccessful();

        $this->block($this->stored('ana@example.com'));

        $this->client->request('POST', '/pt_BR/api/token/refresh');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testTheLoginRecordsTheDate(): void
    {
        $this->makeUser('ana@example.com');
        $this->assertNull($this->stored('ana@example.com')->getLastLoginAt());
        $before = new DateTimeImmutable('-1 second');

        $this->login('ana@example.com', self::PASSWORD, $this->uniqueIp());
        $this->assertResponseIsSuccessful();

        $lastLogin = $this->stored('ana@example.com')->getLastLoginAt();
        $this->assertNotNull($lastLogin);
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $lastLogin->getTimestamp());
    }

    private function makeUser(string $email): User
    {
        $user = UserTestBuilder::create()
            ->withEmail($email)
            ->withPassword('placeholder')
            ->verified()
            ->build();

        $user->setPassword(
            static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, self::PASSWORD),
        );

        $em = $this->em();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    /**
     * Bloqueia direto no banco (sem a API, que também encerraria as sessões).
     */
    private function block(User $user): void
    {
        $em = $this->em();
        $stored = $em->find(User::class, $user->getId());
        $this->assertInstanceOf(User::class, $stored);
        $stored->block('test', new DateTimeImmutable());
        $em->flush();
    }

    private function stored(string $email): User
    {
        $em = $this->em();
        $em->clear();
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertInstanceOf(User::class, $user);

        return $user;
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
     * Um IPv6 aleatório da faixa de documentação (2001:db8::/32).
     */
    private function uniqueIp(): string
    {
        return '2001:db8:' . implode(':', str_split(bin2hex(random_bytes(12)), 4));
    }

    private function em(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        return $em;
    }
}
