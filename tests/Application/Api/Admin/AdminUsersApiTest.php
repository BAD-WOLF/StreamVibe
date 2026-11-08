<?php

declare(strict_types=1);

namespace App\Tests\Application\Api\Admin;

use App\Domain\Audit\Entity\AuditLogEntry;
use App\Domain\Authentication\Entity\RefreshToken;
use App\Domain\Authentication\Entity\ResetPasswordSolicitation;
use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\Role;
use App\Tests\Helper\ApiAuthenticationTrait;
use App\Tests\Helper\ApiRequestTrait;
use App\Tests\Helper\DatabaseTestTrait;
use App\Tests\Helper\UserTestBuilder;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * A administração de contas (/{locale}/api/admin/users...): a lista com
 * filtros, o detalhe e as ações, com a regra de quem administra quem
 * (UserAdministrationPolicy) e o log de auditoria.
 *
 * O KernelBrowser reinicia o kernel a cada requisição: o que o teste
 * prepara vai pelo EntityManager do container atual (em()), e o que lê do
 * banco depois de uma requisição vem dele limpo (fresh()), sem nada em
 * memória.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final class AdminUsersApiTest extends WebTestCase
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

    // ------------------------------------------------------------------
    // Quem entra
    // ------------------------------------------------------------------

    /**
     * @return iterable<string, array{\App\Domain\User\ValueObject\Role}>
     */
    public static function belowAdminProvider(): iterable
    {
        yield 'user' => [Role::User];
        yield 'dev' => [Role::Dev];
    }

    #[DataProvider('belowAdminProvider')]
    public function testOnlyAdminsGetIn(Role $role): void
    {
        $this->authenticateClient($this->client, $this->makeUser('below@example.com', $role));

        $this->client->request('GET', '/pt_BR/api/admin/users');
        $this->assertApiError($this->client, Response::HTTP_FORBIDDEN, 'http_error');

        $this->client->request('GET', '/pt_BR/api/admin/audit-log');
        $this->assertApiError($this->client, Response::HTTP_FORBIDDEN, 'http_error');
    }

    /**
     * O owner herda tudo do admin (role_hierarchy).
     */
    public function testAnOwnerGetsIn(): void
    {
        $this->authenticateClient($this->client, $this->makeUser('owner@example.com', Role::Owner));

        $this->client->request('GET', '/pt_BR/api/admin/users');

        $this->assertResponseIsSuccessful();
    }

    // ------------------------------------------------------------------
    // GET /admin/users
    // ------------------------------------------------------------------

    public function testListsTheAccountsNewestFirst(): void
    {
        $admin = $this->makeUser('admin@example.com', Role::Admin);
        $dev = $this->makeUser('dev@example.com', Role::Dev);
        $pending = $this->makeUser('pending@example.com', verified: false);
        $this->authenticateClient($this->client, $admin);

        $this->client->request('GET', '/pt_BR/api/admin/users');

        $this->assertResponseIsSuccessful();
        $body = $this->responseData($this->client);
        $this->assertSame('Users retrieved successfully', $body['message']);
        $data = $body['data'];
        $this->assertSame(['pending@example.com', 'dev@example.com', 'admin@example.com'], array_column($data['users'], 'email'));
        $this->assertSame(
            ['id', 'email', 'role', 'verified', 'created_at', 'last_login_at', 'blocked_at'],
            array_keys($data['users'][0]),
        );
        $this->assertSame([$pending->getId(), 'ROLE_USER', false], [$data['users'][0]['id'], $data['users'][0]['role'], $data['users'][0]['verified']]);
        $this->assertSame('ROLE_DEV', $data['users'][1]['role']);
        $this->assertNotNull($data['users'][0]['created_at']);
        $this->assertNull($data['users'][0]['blocked_at']);
        $this->assertSame(
            [1, 1, 3, false, false, null, null],
            [$data['current_page'], $data['total_pages'], $data['total_results'], $data['has_next'], $data['has_previous'], $data['next_page'], $data['previous_page']],
        );
        $this->assertSame($dev->getId(), $data['users'][1]['id']);
    }

    public function testFilters(): void
    {
        $this->authenticateClient($this->client, $this->makeUser('admin@example.com', Role::Admin));
        $this->makeUser('dev@example.com', Role::Dev);
        $pending = $this->makeUser('pending@example.com', verified: false);
        $blocked = $this->makeUser('blocked@example.com');
        $this->blockInDatabase($blocked);

        $emails = function (string $query): array {
            $this->client->request('GET', '/pt_BR/api/admin/users?' . $query);
            $this->assertResponseIsSuccessful();

            return array_column($this->responseData($this->client)['data']['users'], 'email');
        };

        $this->assertSame(['dev@example.com'], $emails('role=ROLE_DEV'));
        $this->assertSame(['blocked@example.com', 'pending@example.com'], $emails('role=ROLE_USER'));
        $this->assertSame(['pending@example.com'], $emails('verified=false'));
        $this->assertSame(['blocked@example.com'], $emails('blocked=true'));
        $this->assertSame(['pending@example.com'], $emails('search=PEND'));
        $this->assertSame(['pending@example.com'], $emails('search=' . $pending->getId()));
        $this->assertSame([], $emails('search=' . urlencode('%')));
        $this->assertSame(['blocked@example.com'], $emails('role=ROLE_USER&blocked=1&verified=true'));
    }

    public function testInvalidFiltersAreABadRequest(): void
    {
        $this->authenticateClient($this->client, $this->makeUser('admin@example.com', Role::Admin));

        $this->client->request('GET', '/pt_BR/api/admin/users?role=ROLE_ROOT');
        $error = $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, 'user_validation_error');
        $this->assertSame(['Role: Role must be one of: ROLE_USER, ROLE_DEV, ROLE_ADMIN, ROLE_OWNER'], $error['errors']);

        $this->client->request('GET', '/pt_BR/api/admin/users?page=0');
        $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, 'user_validation_error');

        $this->client->request('GET', '/pt_BR/api/admin/users?verified=maybe');
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testPagination(): void
    {
        $this->authenticateClient($this->client, $this->makeUser('admin@example.com', Role::Admin));
        for ($i = 1; $i <= 21; $i++) {
            $this->makeUser("user{$i}@example.com");
        }

        $this->client->request('GET', '/pt_BR/api/admin/users?page=2');

        $data = $this->responseData($this->client)['data'];
        $this->assertSame(['user1@example.com', 'admin@example.com'], array_column($data['users'], 'email'));
        $this->assertSame([2, 2, 22, false, true, null, 1], [
            $data['current_page'], $data['total_pages'], $data['total_results'],
            $data['has_next'], $data['has_previous'], $data['next_page'], $data['previous_page'],
        ]);
    }

    // ------------------------------------------------------------------
    // GET /admin/users/{userId}
    // ------------------------------------------------------------------

    public function testDetailsOfAnAccount(): void
    {
        $admin = $this->makeUser('admin@example.com', Role::Admin);
        $user = $this->makeUser('ana@example.com');
        $this->addSession($user, '+1 day');
        $this->addSession($user, '-1 day');
        static::getContainer()->get(ResetPasswordHelperInterface::class)->generateResetToken($user);
        $this->authenticateClient($this->client, $admin);

        $this->client->request('GET', '/pt_BR/api/admin/users/' . $user->getId());

        $this->assertResponseIsSuccessful();
        $data = $this->responseData($this->client)['data'];
        $this->assertSame(
            ['id', 'email', 'role', 'verified', 'created_at', 'last_login_at', 'blocked_at', 'blocked_reason',
             'active_sessions', 'password_reset_requests', 'can_manage', 'assignable_roles'],
            array_keys($data),
        );
        $this->assertSame([$user->getId(), 'ana@example.com', 'ROLE_USER', true], [$data['id'], $data['email'], $data['role'], $data['verified']]);
        $this->assertSame(1, $data['active_sessions'], 'the expired session does not count');
        $this->assertCount(1, $data['password_reset_requests']);
        $this->assertSame(['id', 'created_at', 'expires_at'], array_keys($data['password_reset_requests'][0]));
        $this->assertTrue($data['can_manage']);
        $this->assertSame(['ROLE_USER', 'ROLE_DEV'], $data['assignable_roles']);
    }

    public function testAnAdminSeesAnOwnerButCannotChangeIt(): void
    {
        $this->authenticateClient($this->client, $this->makeUser('admin@example.com', Role::Admin));
        $owner = $this->makeUser('owner@example.com', Role::Owner);

        $this->client->request('GET', '/pt_BR/api/admin/users/' . $owner->getId());

        $data = $this->responseData($this->client)['data'];
        $this->assertSame('ROLE_OWNER', $data['role']);
        $this->assertFalse($data['can_manage']);
        $this->assertSame([], $data['assignable_roles']);
    }

    public function testAnUnknownAccountIsNotFound(): void
    {
        $this->authenticateClient($this->client, $this->makeUser('admin@example.com', Role::Admin));

        $this->client->request('GET', '/pt_BR/api/admin/users/999999');

        $this->assertApiError($this->client, Response::HTTP_NOT_FOUND, 'user_not_found_error');
    }

    // ------------------------------------------------------------------
    // Ações
    // ------------------------------------------------------------------

    public function testVerify(): void
    {
        $admin = $this->makeUser('admin@example.com', Role::Admin);
        $user = $this->makeUser('pending@example.com', verified: false);
        $this->authenticateClient($this->client, $admin);

        $this->client->request('POST', '/pt_BR/api/admin/users/' . $user->getId() . '/verify');

        $this->assertResponseIsSuccessful();
        $this->assertSame(['user_id' => $user->getId(), 'verified' => true], $this->responseData($this->client)['data']);
        $this->assertTrue($this->find($user)->isVerified());
        $this->assertEntry('user_verified', $admin, $user, ['role' => 'ROLE_USER']);

        $this->client->request('POST', '/pt_BR/api/admin/users/' . $user->getId() . '/verify');
        $this->assertApiError($this->client, Response::HTTP_CONFLICT, 'business_logic_error');
    }

    public function testResendVerification(): void
    {
        $admin = $this->makeUser('admin@example.com', Role::Admin);
        $user = $this->makeUser('pending@example.com', verified: false);
        $this->authenticateClient($this->client, $admin);

        $this->client->request('POST', '/pt_BR/api/admin/users/' . $user->getId() . '/resend-verification');

        $this->assertResponseIsSuccessful();
        $this->assertSame(['user_id' => $user->getId(), 'email_sent' => true], $this->responseData($this->client)['data']);
        $this->assertEmailCount(1);
        $this->assertEntry('verification_resent', $admin, $user, ['role' => 'ROLE_USER']);

        $verified = $this->makeUser('verified@example.com');
        $this->client->request('POST', '/pt_BR/api/admin/users/' . $verified->getId() . '/resend-verification');
        $this->assertApiError($this->client, Response::HTTP_CONFLICT, 'business_logic_error');
    }

    public function testBlockAndUnblock(): void
    {
        $admin = $this->makeUser('admin@example.com', Role::Admin);
        $user = $this->makeUser('ana@example.com');
        $this->addSession($user, '+1 day');
        $this->authenticateClient($this->client, $admin);
        $uri = '/pt_BR/api/admin/users/' . $user->getId();

        $this->postJson($this->client, $uri . '/block', ['reason' => 'spam']);

        $this->assertResponseIsSuccessful();
        $data = $this->responseData($this->client)['data'];
        $this->assertSame(['user_id', 'blocked_at', 'revoked_sessions'], array_keys($data));
        $this->assertNotNull($data['blocked_at']);
        $this->assertSame(1, $data['revoked_sessions']);
        $stored = $this->find($user);
        $this->assertTrue($stored->isBlocked());
        $this->assertSame('spam', $stored->getBlockedReason());
        $this->assertSame(0, $this->sessionCount($user));
        $this->assertEntry('user_blocked', $admin, $user, ['role' => 'ROLE_USER', 'reason' => 'spam', 'revoked_sessions' => 1]);

        $this->postJson($this->client, $uri . '/block', ['reason' => 'again']);
        $this->assertApiError($this->client, Response::HTTP_CONFLICT, 'business_logic_error');

        $this->client->request('POST', $uri . '/unblock');
        $this->assertResponseIsSuccessful();
        $this->assertSame(['user_id' => $user->getId(), 'blocked_at' => null], $this->responseData($this->client)['data']);
        $this->assertFalse($this->find($user)->isBlocked());

        $this->client->request('POST', $uri . '/unblock');
        $this->assertApiError($this->client, Response::HTTP_CONFLICT, 'business_logic_error');
    }

    public function testBlockNeedsAReason(): void
    {
        $this->authenticateClient($this->client, $this->makeUser('admin@example.com', Role::Admin));
        $user = $this->makeUser('ana@example.com');

        $this->postJson($this->client, '/pt_BR/api/admin/users/' . $user->getId() . '/block', ['reason' => '  ']);
        $error = $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, 'user_validation_error');
        $this->assertSame(['Reason: Reason cannot be blank'], $error['errors']);

        $this->postJson($this->client, '/pt_BR/api/admin/users/' . $user->getId() . '/block', '[1, 2]');
        $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, 'user_validation_error');

        $this->assertFalse($this->find($user)->isBlocked());
    }

    public function testRoleChangesFollowTheHierarchy(): void
    {
        $admin = $this->makeUser('admin@example.com', Role::Admin);
        $user = $this->makeUser('ana@example.com');
        $otherAdmin = $this->makeUser('other-admin@example.com', Role::Admin);
        $this->authenticateClient($this->client, $admin);

        $this->postJson($this->client, '/pt_BR/api/admin/users/' . $user->getId() . '/role', ['role' => 'ROLE_DEV']);
        $this->assertResponseIsSuccessful();
        $this->assertSame(
            ['user_id' => $user->getId(), 'role' => 'ROLE_DEV', 'previous_role' => 'ROLE_USER'],
            $this->responseData($this->client)['data'],
        );
        $this->assertSame(Role::Dev, $this->find($user)->getRole());
        $this->assertEntry('role_changed', $admin, $user, ['role' => 'ROLE_DEV', 'previous_role' => 'ROLE_USER']);

        // o admin não dá o próprio papel nem mexe em outro admin
        $this->postJson($this->client, '/pt_BR/api/admin/users/' . $user->getId() . '/role', ['role' => 'ROLE_ADMIN']);
        $this->assertApiError($this->client, Response::HTTP_FORBIDDEN, 'permission_denied_error');
        $this->postJson($this->client, '/pt_BR/api/admin/users/' . $otherAdmin->getId() . '/role', ['role' => 'ROLE_USER']);
        $this->assertApiError($this->client, Response::HTTP_FORBIDDEN, 'permission_denied_error');
        // nem o próprio papel
        $this->postJson($this->client, '/pt_BR/api/admin/users/' . $admin->getId() . '/role', ['role' => 'ROLE_USER']);
        $this->assertApiError($this->client, Response::HTTP_FORBIDDEN, 'permission_denied_error');

        $this->postJson($this->client, '/pt_BR/api/admin/users/' . $user->getId() . '/role', ['role' => 'ROLE_DEV']);
        $this->assertApiError($this->client, Response::HTTP_CONFLICT, 'business_logic_error');
        $this->postJson($this->client, '/pt_BR/api/admin/users/' . $user->getId() . '/role', ['role' => 'ROLE_ROOT']);
        $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, 'user_validation_error');
    }

    public function testAnOwnerGivesAnyRole(): void
    {
        $this->authenticateClient($this->client, $this->makeUser('owner@example.com', Role::Owner));
        $admin = $this->makeUser('admin@example.com', Role::Admin);

        $this->postJson($this->client, '/pt_BR/api/admin/users/' . $admin->getId() . '/role', ['role' => 'ROLE_OWNER']);

        $this->assertResponseIsSuccessful();
        $this->assertSame(Role::Owner, $this->find($admin)->getRole());
    }

    /**
     * A conta promovida usa o papel novo já na requisição seguinte, com o
     * mesmo access token (a conta é lida do banco a cada requisição).
     */
    public function testTheNewRoleCountsOnTheNextRequest(): void
    {
        $owner = $this->makeUser('owner@example.com', Role::Owner);
        $user = $this->makeUser('ana@example.com');
        $userToken = $this->authenticateClient($this->client, $user);

        $this->client->request('GET', '/pt_BR/api/admin/users');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $this->authenticateClient($this->client, $owner);
        $this->postJson($this->client, '/pt_BR/api/admin/users/' . $user->getId() . '/role', ['role' => 'ROLE_ADMIN']);
        $this->assertResponseIsSuccessful();

        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . $userToken);
        $this->client->request('GET', '/pt_BR/api/admin/users');
        $this->assertResponseIsSuccessful();
    }

    public function testRevokeSessions(): void
    {
        $admin = $this->makeUser('admin@example.com', Role::Admin);
        $user = $this->makeUser('ana@example.com');
        $this->addSession($user, '+1 day');
        $this->addSession($user, '+2 days');
        $this->addSession($user, '-1 day');
        $this->authenticateClient($this->client, $admin);

        $this->client->request('DELETE', '/pt_BR/api/admin/users/' . $user->getId() . '/sessions');

        $this->assertResponseIsSuccessful();
        $this->assertSame(['user_id' => $user->getId(), 'revoked_sessions' => 2], $this->responseData($this->client)['data']);
        $this->assertSame(0, $this->sessionCount($user), 'the expired one goes too');
        $this->assertEntry('sessions_revoked', $admin, $user, ['role' => 'ROLE_USER', 'revoked_sessions' => 2]);
    }

    public function testDelete(): void
    {
        $admin = $this->makeUser('admin@example.com', Role::Admin);
        $user = $this->makeUser('ana@example.com', Role::Dev);
        $userId = (int) $user->getId();
        $this->addSession($user, '+1 day');
        static::getContainer()->get(ResetPasswordHelperInterface::class)->generateResetToken($user);
        $this->authenticateClient($this->client, $admin);

        $this->client->request('DELETE', '/pt_BR/api/admin/users/' . $userId);

        $this->assertResponseIsSuccessful();
        $this->assertSame(['user_id' => $userId, 'revoked_sessions' => 1], $this->responseData($this->client)['data']);
        $em = $this->fresh();
        $this->assertNull($em->find(User::class, $userId));
        $this->assertSame(0, $em->getRepository(ResetPasswordSolicitation::class)->count([]), 'ON DELETE CASCADE');
        $this->assertSame(0, $em->getRepository(RefreshToken::class)->count(['username' => 'ana@example.com']));

        $entries = $em->getRepository(AuditLogEntry::class)->findAll();
        $this->assertCount(1, $entries);
        $this->assertSame(['user_deleted', $userId, 'ana@example.com', ['role' => 'ROLE_DEV', 'revoked_sessions' => 1]], [
            $entries[0]->action->value, $entries[0]->targetUserId, $entries[0]->targetEmail, $entries[0]->details,
        ]);

        $this->client->request('DELETE', '/pt_BR/api/admin/users/' . $userId);
        $this->assertApiError($this->client, Response::HTTP_NOT_FOUND, 'user_not_found_error');
    }

    public function testAnAdminCannotTouchAnOwner(): void
    {
        $this->authenticateClient($this->client, $this->makeUser('admin@example.com', Role::Admin));
        $owner = $this->makeUser('owner@example.com', Role::Owner);
        $uri = '/pt_BR/api/admin/users/' . $owner->getId();

        foreach ([['POST', '/verify'], ['POST', '/unblock'], ['DELETE', '/sessions'], ['DELETE', '']] as [$method, $path]) {
            $this->client->request($method, $uri . $path);
            $this->assertApiError($this->client, Response::HTTP_FORBIDDEN, 'permission_denied_error');
        }

        $this->postJson($this->client, $uri . '/block', ['reason' => 'spam']);
        $this->assertApiError($this->client, Response::HTTP_FORBIDDEN, 'permission_denied_error');

        $this->assertFalse($this->find($owner)->isBlocked());
        $this->assertSame([], $this->fresh()->getRepository(AuditLogEntry::class)->findAll());
    }

    // ------------------------------------------------------------------

    private function makeUser(string $email, Role $role = Role::User, bool $verified = true): User
    {
        $user = UserTestBuilder::create()
            ->withEmail($email)
            ->withPassword('hashed_password_123')
            ->verified($verified)
            ->build();
        $user->assignRole($role);

        $em = $this->em();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function blockInDatabase(User $user): void
    {
        $em = $this->em();
        $stored = $em->find(User::class, $user->getId());
        $this->assertInstanceOf(User::class, $stored);
        $stored->block('test', new \DateTimeImmutable());
        $em->flush();
    }

    /**
     * Uma sessão (refresh token) da conta que vence em $validity
     * ("+1 day"; "-1 day" = já vencida).
     */
    private function addSession(User $user, string $validity): void
    {
        $token = RefreshToken::createForUserWithTtl(bin2hex(random_bytes(32)), $user, 3600);
        $token->setValid(new DateTime($validity));

        $em = $this->em();
        $em->persist($token);
        $em->flush();
    }

    private function sessionCount(User $user): int
    {
        return $this->fresh()->getRepository(RefreshToken::class)->count(['username' => $user->getEmail()]);
    }

    private function find(User $user): User
    {
        $stored = $this->fresh()->find(User::class, $user->getId());
        $this->assertInstanceOf(User::class, $stored);

        return $stored;
    }

    /**
     * A única entrada do log, com quem fez, a conta e os detalhes.
     *
     * @param array<string, scalar|null> $details
     */
    private function assertEntry(string $action, User $actor, User $target, array $details): void
    {
        $entries = $this->fresh()->getRepository(AuditLogEntry::class)->findAll();
        $this->assertCount(1, $entries);
        $entry = $entries[0];

        $this->assertSame($action, $entry->action->value);
        $this->assertSame([$actor->getId(), $actor->getEmail()], [$entry->actorId, $entry->actorEmail]);
        $this->assertSame([$target->getId(), $target->getEmail()], [$entry->targetUserId, $entry->targetEmail]);
        $this->assertSame($details, $entry->details);
    }

    /**
     * O EntityManager do container atual, para preparar o teste: as contas
     * criadas continuam gerenciadas por ele (o pedido de troca de senha, por
     * exemplo, precisa da conta gerenciada).
     */
    private function em(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        return $em;
    }

    /**
     * O mesmo EntityManager, sem nada em memória: lê o que as requisições
     * gravaram.
     */
    private function fresh(): EntityManagerInterface
    {
        $em = $this->em();
        $em->clear();

        return $em;
    }
}
