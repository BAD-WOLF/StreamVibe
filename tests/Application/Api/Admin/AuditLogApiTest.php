<?php

declare(strict_types=1);

namespace App\Tests\Application\Api\Admin;

use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\Role;
use App\Tests\Helper\ApiAuthenticationTrait;
use App\Tests\Helper\ApiRequestTrait;
use App\Tests\Helper\DatabaseTestTrait;
use App\Tests\Helper\UserTestBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * GET /{locale}/api/admin/audit-log: o que as ações da administração
 * registraram, da entrada mais nova para a mais antiga, com os filtros de
 * conta e de ação. As entradas continuam lá depois que a conta é
 * excluída.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class AuditLogApiTest extends WebTestCase
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

    public function testTheActionsShowUpNewestFirst(): void
    {
        $owner = $this->makeUser('owner@example.com', Role::Owner);
        $ana = $this->makeUser('ana@example.com', Role::User, verified: false);
        $bob = $this->makeUser('bob@example.com', Role::User);
        $anaId = (int) $ana->getId();
        $this->authenticateClient($this->client, $owner);

        $this->client->request('POST', "/pt_BR/api/admin/users/{$anaId}/verify");
        $this->assertResponseIsSuccessful();
        $this->postJson($this->client, '/pt_BR/api/admin/users/' . $bob->getId() . '/block', ['reason' => 'spam']);
        $this->assertResponseIsSuccessful();
        $this->postJson($this->client, "/pt_BR/api/admin/users/{$anaId}/role", ['role' => 'ROLE_ADMIN']);
        $this->assertResponseIsSuccessful();
        $this->client->request('DELETE', "/pt_BR/api/admin/users/{$anaId}");
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/pt_BR/api/admin/audit-log');

        $this->assertResponseIsSuccessful();
        $body = $this->responseData($this->client);
        $this->assertSame('Audit log retrieved successfully', $body['message']);
        $entries = $body['data']['entries'];
        $this->assertSame(['user_deleted', 'role_changed', 'user_blocked', 'user_verified'], array_column($entries, 'action'));
        $this->assertSame(['id', 'action', 'occurred_at', 'actor', 'target', 'details'], array_keys($entries[0]));
        $this->assertSame(['id' => $owner->getId(), 'email' => 'owner@example.com'], $entries[0]['actor']);
        $this->assertSame(['id' => $anaId, 'email' => 'ana@example.com'], $entries[0]['target'], 'the deleted account stays in the log');
        $this->assertSame(['role' => 'ROLE_ADMIN', 'previous_role' => 'ROLE_USER'], $entries[1]['details']);
        $this->assertSame(['role' => 'ROLE_USER', 'reason' => 'spam', 'revoked_sessions' => 0], $entries[2]['details']);
        $this->assertSame([1, 1, 4], [$body['data']['current_page'], $body['data']['total_pages'], $body['data']['total_results']]);

        $this->client->request('GET', "/pt_BR/api/admin/audit-log?user_id={$anaId}");
        $this->assertSame(['user_deleted', 'role_changed', 'user_verified'], array_column($this->responseData($this->client)['data']['entries'], 'action'));

        $this->client->request('GET', '/pt_BR/api/admin/audit-log?action=user_blocked');
        $this->assertSame(['bob@example.com'], array_column(array_column($this->responseData($this->client)['data']['entries'], 'target'), 'email'));
    }

    public function testInvalidFiltersAreABadRequest(): void
    {
        $this->authenticateClient($this->client, $this->makeUser('admin@example.com', Role::Admin));

        $this->client->request('GET', '/pt_BR/api/admin/audit-log?action=user_hacked');
        $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, 'user_validation_error');

        $this->client->request('GET', '/pt_BR/api/admin/audit-log?user_id=0');
        $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, 'user_validation_error');
    }

    private function makeUser(string $email, Role $role, bool $verified = true): User
    {
        $user = UserTestBuilder::create()
            ->withEmail($email)
            ->withPassword('hashed_password_123')
            ->verified($verified)
            ->build();
        $user->assignRole($role);

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }
}
