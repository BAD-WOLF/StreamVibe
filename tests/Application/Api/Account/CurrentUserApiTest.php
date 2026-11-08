<?php

declare(strict_types=1);

namespace App\Tests\Application\Api\Account;

use App\Domain\User\Entity\User;
use App\Tests\Helper\ApiAuthenticationTrait;
use App\Tests\Helper\ApiRequestTrait;
use App\Tests\Helper\DatabaseTestTrait;
use App\Tests\Helper\UserTestBuilder;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * GET /{locale}/api/me: a conta do usuário do JWT, lida do banco.
 *
 * NOTA (nov/2026): criado com o GET /me.
 */
final class CurrentUserApiTest extends WebTestCase
{
    use DatabaseTestTrait;
    use ApiAuthenticationTrait;
    use ApiRequestTrait;

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

    public function testReturnsTheAccountOfTheUserOfTheJwt(): void
    {
        $user = $this->makeUser('me@example.com', ['ROLE_ADMIN']);
        $this->authenticateClient($this->client, $user);

        $this->client->request('GET', '/pt_BR/api/me');

        $this->assertResponseIsSuccessful();
        $body = $this->responseData($this->client);
        $this->assertTrue($body['success']);
        $this->assertSame(
            [
                'id' => $user->getId(),
                'email' => 'me@example.com',
                'roles' => ['ROLE_ADMIN', 'ROLE_USER'],
                'verified' => true,
            ],
            $body['data'],
        );
        $this->assertSame('Current user retrieved successfully', $body['message']);
    }

    /**
     * Os dados vêm do banco a cada chamada, não do JWT: um papel trocado
     * depois do login aparece na hora.
     */
    public function testAChangeAfterTheLoginShowsUpAtOnce(): void
    {
        $user = $this->makeUser('promoted@example.com', []);
        $this->authenticateClient($this->client, $user);

        $this->client->request('GET', '/en/api/me');
        $this->assertSame(['ROLE_USER'], $this->responseData($this->client)['data']['roles']);

        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $stored = $entityManager->getRepository(User::class)->find($user->getId());
        $this->assertInstanceOf(User::class, $stored);
        $stored->setRoles(['ROLE_ADMIN']);
        $entityManager->flush();

        $this->client->request('GET', '/en/api/me');
        $this->assertSame(['ROLE_ADMIN', 'ROLE_USER'], $this->responseData($this->client)['data']['roles']);
    }

    public function testRequiresAJwt(): void
    {
        $this->client->request('GET', '/pt_BR/api/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @param list<string> $roles
     */
    private function makeUser(string $email, array $roles): User
    {
        $user = UserTestBuilder::create()
            ->withEmail($email)
            ->withPassword('hashed_password_123')
            ->withRoles($roles)
            ->verified()
            ->build();

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
