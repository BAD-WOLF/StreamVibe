<?php

declare(strict_types=1);

namespace App\Tests\Application\Api\Authentication;

use App\Domain\Authentication\Entity\RefreshToken;
use App\Domain\User\Entity\User;
use App\Tests\Helper\ApiRequestTrait;
use App\Tests\Helper\DatabaseTestTrait;
use App\Tests\Helper\UserTestBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie as BrowserCookie;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * A sessão: login, renovação e logout com o refresh token no cookie
 * (config/packages/gesdinet_jwt_refresh_token.yaml).
 *
 * - POST /api/login_check: devolve só o access token no corpo e o refresh
 *   token num cookie HttpOnly, Secure, SameSite=Strict;
 * - POST /{locale}/api/token/refresh: lê o cookie, devolve outro access
 *   token e troca o refresh token (o usado deixa de valer);
 * - POST /{locale}/api/logout: apaga o refresh token do banco e manda o
 *   navegador apagar o cookie.
 *
 * As requisições vão por HTTPS (setUp): o cookie é Secure, e o BrowserKit,
 * como o navegador, só o devolve por HTTPS.
 *
 * NOTA (nov/2026): criado quando o refresh token saiu do corpo das
 * respostas (e do localStorage do front) para o cookie.
 */
final class SessionApiTest extends WebTestCase
{
    use DatabaseTestTrait;
    use ApiRequestTrait;

    private const string PASSWORD = 'SecurePassword123!';

    private const string COOKIE = 'refresh_token';

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

    // ------------------------------------------------------------------
    // POST /api/login_check
    // ------------------------------------------------------------------

    public function testLoginReturnsOnlyTheAccessTokenAndSetsTheRefreshCookie(): void
    {
        $this->makeUser('session@example.com');

        $this->login('session@example.com');

        $this->assertResponseIsSuccessful();
        $this->assertSame(['token'], array_keys($this->responseData($this->client)));

        $cookie = $this->responseCookie();
        $this->assertNotNull($cookie, 'The login did not set the refresh token cookie');
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->isSecure());
        $this->assertSame(Cookie::SAMESITE_STRICT, $cookie->getSameSite());
        $this->assertSame('/', $cookie->getPath());
        $this->assertNull($cookie->getDomain(), 'The cookie must stay on the API host (host-only)');
        $this->assertGreaterThan(time() + 2592000 - 60, $cookie->getExpiresTime());

        $this->assertSame([$cookie->getValue()], $this->refreshTokensOf('session@example.com'));
    }

    /**
     * Outra conta no mesmo navegador: o login apaga o refresh token que
     * chegou no cookie (o da sessão anterior) e cria um novo, da conta que
     * entrou. Sem single_use, o login devolvia o token da conta anterior.
     */
    public function testLoginWithAnotherAccountReplacesThePreviousSession(): void
    {
        $this->makeUser('first@example.com');
        $this->makeUser('second@example.com');

        $this->login('first@example.com');
        $firstCookie = $this->responseCookie();
        $this->assertNotNull($firstCookie);

        $this->login('second@example.com');
        $this->assertResponseIsSuccessful();
        $secondCookie = $this->responseCookie();
        $this->assertNotNull($secondCookie);

        $this->assertNotSame($firstCookie->getValue(), $secondCookie->getValue());
        $this->assertSame([], $this->refreshTokensOf('first@example.com'));
        $this->assertSame([$secondCookie->getValue()], $this->refreshTokensOf('second@example.com'));

        $this->client->request('POST', '/pt_BR/api/token/refresh');
        $this->assertResponseIsSuccessful();
        $this->assertSame('second@example.com', $this->jwtUsername((string) $this->responseData($this->client)['token']));
    }

    // ------------------------------------------------------------------
    // POST /{locale}/api/token/refresh
    // ------------------------------------------------------------------

    public function testRefreshReadsTheCookieAndRotatesTheRefreshToken(): void
    {
        $this->makeUser('rotate@example.com');
        $this->login('rotate@example.com');
        $loginCookie = $this->responseCookie();
        $this->assertNotNull($loginCookie);

        $this->client->request('POST', '/pt_BR/api/token/refresh');

        $this->assertResponseIsSuccessful();
        $data = $this->responseData($this->client);
        $this->assertSame(['token'], array_keys($data));
        $this->assertSame('rotate@example.com', $this->jwtUsername((string) $data['token']));

        $rotatedCookie = $this->responseCookie();
        $this->assertNotNull($rotatedCookie, 'The refresh did not send the new refresh token');
        $this->assertNotSame($loginCookie->getValue(), $rotatedCookie->getValue());
        $this->assertTrue($rotatedCookie->isHttpOnly());
        $this->assertTrue($rotatedCookie->isSecure());
        $this->assertSame([$rotatedCookie->getValue()], $this->refreshTokensOf('rotate@example.com'));
    }

    /**
     * O refresh token usado não vale mais: quem copiou o cookie perde a
     * sessão na próxima renovação do dono.
     */
    public function testARotatedRefreshTokenIsRejected(): void
    {
        $this->makeUser('reuse@example.com');
        $this->login('reuse@example.com');
        $loginCookie = $this->responseCookie();
        $this->assertNotNull($loginCookie);

        $this->client->request('POST', '/pt_BR/api/token/refresh');
        $this->assertResponseIsSuccessful();

        $this->useRefreshCookie($loginCookie->getValue());
        $this->client->request('POST', '/pt_BR/api/token/refresh');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testRefreshWithoutTheCookieIsUnauthorized(): void
    {
        $this->client->request('POST', '/pt_BR/api/token/refresh');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    // ------------------------------------------------------------------
    // POST /{locale}/api/logout
    // ------------------------------------------------------------------

    public function testLogoutRevokesTheRefreshTokenAndClearsTheCookie(): void
    {
        $this->makeUser('logout@example.com');
        $this->login('logout@example.com');
        $loginCookie = $this->responseCookie();
        $this->assertNotNull($loginCookie);

        $this->client->request('POST', '/pt_BR/api/logout');

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            ['code' => 200, 'message' => 'The supplied refresh_token has been invalidated.'],
            $this->responseData($this->client),
        );

        $cleared = $this->responseCookie();
        $this->assertNotNull($cleared, 'The logout did not clear the cookie');
        $this->assertTrue($cleared->isCleared());
        $this->assertSame([], $this->refreshTokensOf('logout@example.com'));

        $this->useRefreshCookie($loginCookie->getValue());
        $this->client->request('POST', '/pt_BR/api/token/refresh');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * O logout vale em qualquer locale e não depende do access token (este
     * cliente nunca manda o header Authorization): quem sai pode estar com
     * o JWT vencido, ou sem nenhum, numa aba que acabou de abrir.
     */
    public function testLogoutWorksInAnyLocaleWithoutTheAccessToken(): void
    {
        $this->makeUser('no-jwt@example.com');
        $this->login('no-jwt@example.com');

        $this->client->request('POST', '/en/api/logout');

        $this->assertResponseIsSuccessful();
        $this->assertSame([], $this->refreshTokensOf('no-jwt@example.com'));
    }

    public function testLogoutWithoutTheCookieIsABadRequest(): void
    {
        $this->client->request('POST', '/pt_BR/api/logout');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertSame(['code' => 400, 'message' => 'No refresh_token found.'], $this->responseData($this->client));
    }

    public function testLogoutOnlyAcceptsPost(): void
    {
        $this->client->request('GET', '/pt_BR/api/logout');

        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    // ------------------------------------------------------------------

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

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function login(string $email): void
    {
        $this->postJson($this->client, '/api/login_check', [
            'email' => $email,
            'password' => self::PASSWORD,
        ]);
    }

    /**
     * O cookie do refresh token que a última resposta mandou (criado ou
     * apagado), ou null.
     */
    private function responseCookie(): ?Cookie
    {
        foreach ($this->client->getResponse()->headers->getCookies() as $cookie) {
            if ($cookie->getName() === self::COOKIE) {
                return $cookie;
            }
        }

        return null;
    }

    /**
     * Troca o cookie do refresh token do navegador de teste por $value (o
     * mesmo host e path que o cookie de verdade tem).
     */
    private function useRefreshCookie(string $value): void
    {
        $cookieJar = $this->client->getCookieJar();
        $cookieJar->clear();
        $cookieJar->set(new BrowserCookie(self::COOKIE, $value, null, '/', 'localhost', true, true, false, 'strict'));
    }

    /**
     * Os refresh tokens de uma conta que estão no banco.
     *
     * @return list<string>
     */
    private function refreshTokensOf(string $email): array
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->clear();

        $tokens = $entityManager->getRepository(RefreshToken::class)->findBy(['username' => $email]);

        return array_values(array_map(
            static fn(RefreshToken $token): string => (string) $token->getRefreshToken(),
            $tokens,
        ));
    }

    /**
     * O "username" (email) do payload de um JWT.
     */
    private function jwtUsername(string $jwt): ?string
    {
        $parts = explode('.', $jwt);
        $this->assertCount(3, $parts, 'Not a JWT');

        $payload = json_decode(
            (string) base64_decode(strtr($parts[1], '-_', '+/'), true),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        return $payload['username'] ?? null;
    }
}
