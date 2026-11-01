<?php

declare(strict_types=1);

namespace App\Tests\Application\Api\Authentication;

use App\Domain\Authentication\Entity\RefreshToken;
use App\Domain\Authentication\Entity\ResetPasswordSolicitation;
use App\Domain\User\Entity\User;
use App\Infrastructure\Persistence\Repository\UserRepository;
use App\Tests\Helper\ApiAuthenticationTrait;
use App\Tests\Helper\ApiRequestTrait;
use App\Tests\Helper\DatabaseTestTrait;
use App\Tests\Helper\UserTestBuilder;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * API tests for password reset endpoints (solicitation, check-status,
 * cancel, confirm, user-requests) through HTTP real, no mock.
 *
 * NOTA: até esta suíte, nenhum destes endpoints tinha teste de API —
 * só existia teste unitário do ResetUserPasswordUseCase isolado, que
 * nunca passa pelo controller. Foi exatamente essa lacuna que deixou
 * passar um bug crítico (ResetPasswordSolicitationController chamando
 * um método inexistente no UseCase, quebrando 100% das solicitações de
 * reset em produção) até ser achado manualmente no Swagger.
 *
 * NOTA 2: para os testes de check-status/cancel/confirm, geramos o token
 * real direto via ResetPasswordHelperInterface do container, em vez de
 * extrair da URL dentro do corpo do email.
 * ResetPasswordHelperInterface::generateResetToken() é o mesmo mecanismo
 * que o PasswordResetManager usa — ResetPasswordToken::getToken() devolve
 * o token completo (selector + verificador) em texto puro.
 *
 * NOTA 3 (out/2026): status/{token}, cancel e confirm continuam sem
 * token de propósito — são públicos no security.yaml (quem está trocando
 * a senha não está logado), e estes testes provam isso.
 * requests/{userId} exige JWT: os testes dessa rota autenticam
 * (ApiAuthenticationTrait) e testGetUserResetRequestsRequiresAuthentication()
 * garante o 401 sem token.
 *
 * NOTA 4 (out/2026):
 * - As respostas de sucesso agora têm o envelope do auto-mapping
 *   ({"success", "data", "message"}); os campos que os testes liam no
 *   nível de cima (email_sent, valid...) estão em `data`. O `count` de
 *   requests/{userId} saiu (era o tamanho de active_requests).
 * - Entraram os testes do IDOR de requests/{userId} (403 para outro
 *   usuário, 200 para ROLE_ADMIN), do endpoint novo de confirmação e do
 *   token vencido (410).
 */
final class ResetPasswordApiTest extends WebTestCase
{
    use DatabaseTestTrait;
    use ApiAuthenticationTrait;
    use ApiRequestTrait;

    private const string NEW_PASSWORD = "BrandNewSecurePassword456!";

    private UserRepository $userRepository;
    private KernelBrowser $client;
    private ResetPasswordHelperInterface $resetPasswordHelper;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine')->getManager();
        $this->userRepository = $this->entityManager->getRepository(User::class);
        $this->resetPasswordHelper = $container->get(ResetPasswordHelperInterface::class);

        $this->setupTestDatabase();
    }

    protected function tearDown(): void
    {
        $this->teardownTestDatabase();
        parent::tearDown();
    }

    private function makeVerifiedUser(string $email): User
    {
        $user = UserTestBuilder::create()
            ->withEmail($email)
            ->withPassword('hashed_password_123')
            ->verified()
            ->build();

        $this->userRepository->save($user);

        return $user;
    }

    // ------------------------------------------------------------------
    // POST /reset-password (solicitation)
    // ------------------------------------------------------------------

    public function testSolicitationSuccessSendsResetEmail(): void
    {
        $this->makeVerifiedUser('resetme@example.com');

        $this->postJson($this->client, '/pt_BR/api/reset-password', ['email' => 'resetme@example.com']);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = $this->responseData($this->client);
        $this->assertTrue($data['success']);
        $this->assertSame(['email_sent', 'expires_at'], array_keys($data['data']));
        $this->assertTrue($data['data']['email_sent']);
        $this->assertNotFalse(DateTimeImmutable::createFromFormat(DATE_RFC3339, $data['data']['expires_at']));
        $this->assertIsString($data['message']);

        $this->assertEmailCount(1);
    }

    /**
     * user_id e request_id saíram da resposta: davam o id interno da
     * conta para quem só sabe o email.
     */
    public function testSolicitationDoesNotExposeInternalIds(): void
    {
        $this->makeVerifiedUser('ids@example.com');

        $this->postJson($this->client, '/pt_BR/api/reset-password', ['email' => 'ids@example.com']);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = $this->responseData($this->client);
        $this->assertArrayNotHasKey('user_id', $data['data']);
        $this->assertArrayNotHasKey('request_id', $data['data']);
    }

    public function testSolicitationReturnsGenericResponseForNonexistentEmail(): void
    {
        // NOTA: por segurança, o UseCase não revela se o email existe —
        // retorna 200 genérico tanto para email existente quanto
        // inexistente.
        $this->postJson($this->client, '/pt_BR/api/reset-password', ['email' => 'ghost-nobody@example.com']);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = $this->responseData($this->client);
        $this->assertFalse($data['data']['email_sent']);
        $this->assertNull($data['data']['expires_at']);

        $this->assertEmailCount(0);
    }

    public function testSolicitationFailsForUnverifiedUser(): void
    {
        $user = UserTestBuilder::create()
            ->withEmail('unverified-reset@example.com')
            ->withPassword('hashed_password_123')
            ->build(); // não chama ->verified()

        $this->userRepository->save($user);

        $this->postJson($this->client, '/pt_BR/api/reset-password', ['email' => 'unverified-reset@example.com']);

        $error = $this->assertApiError($this->client, Response::HTTP_FORBIDDEN, 'user_not_verified_error');
        $this->assertSame('email_verification', $error['action_required']);
        $this->assertEmailCount(0);
    }

    public function testSolicitationFailsWithMissingEmail(): void
    {
        $this->postJson($this->client, '/pt_BR/api/reset-password', []);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testSolicitationFailsWithInvalidBody(): void
    {
        $this->postJson($this->client, '/pt_BR/api/reset-password', 'not a json object');

        $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, 'user_validation_error');
    }

    public function testSolicitationReturnsExistingRequestWhenActiveRequestExists(): void
    {
        $user = $this->makeVerifiedUser('already-active@example.com');

        // Gera uma solicitação ativa de verdade antes da chamada HTTP,
        // pra exercitar o branch "já existe solicitação ativa" do UseCase.
        $this->resetPasswordHelper->generateResetToken($user);

        $this->postJson($this->client, '/pt_BR/api/reset-password', ['email' => 'already-active@example.com']);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = $this->responseData($this->client);
        // Reaproveita a solicitação existente — não dispara novo email.
        $this->assertFalse($data['data']['email_sent']);
        $this->assertNotNull($data['data']['expires_at']);

        $this->assertEmailCount(0);
    }

    /**
     * O antigo cleanupExpiredRequests() nunca removia nada (percorria só
     * as solicitações NÃO vencidas): agora a vencida sai e um link novo
     * é enviado.
     */
    public function testSolicitationReplacesAnExpiredRequest(): void
    {
        $user = $this->makeVerifiedUser('expired-before@example.com');
        $this->resetPasswordHelper->generateResetToken($user);
        $this->expireResetRequestsOf($user);

        $this->postJson($this->client, '/pt_BR/api/reset-password', ['email' => 'expired-before@example.com']);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertTrue($this->responseData($this->client)['data']['email_sent']);
        $this->assertEmailCount(1);
        $this->assertEntityCount(ResetPasswordSolicitation::class, 1);
    }

    // ------------------------------------------------------------------
    // GET /reset-password/status/{token}
    // ------------------------------------------------------------------

    public function testCheckStatusReturnsValidForFreshToken(): void
    {
        $user = $this->makeVerifiedUser('checkstatus@example.com');
        $resetToken = $this->resetPasswordHelper->generateResetToken($user);

        $this->client->request(
            'GET',
            '/pt_BR/api/reset-password/status/' . $resetToken->getToken(),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = $this->responseData($this->client);
        $this->assertSame(['valid', 'expired', 'used', 'expires_at'], array_keys($data['data']));
        $this->assertTrue($data['data']['valid']);
        $this->assertFalse($data['data']['expired']);
        $this->assertFalse($data['data']['used']);
    }

    public function testCheckStatusReturns400ForNonexistentToken(): void
    {
        // Token bem formado (40 chars) mas que nunca foi gerado.
        $fakeToken = str_repeat('a', 40);

        $this->client->request(
            'GET',
            '/pt_BR/api/reset-password/status/' . $fakeToken,
        );

        $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, 'invalid_reset_token_error');
    }

    public function testCheckStatusReturns400ForTooShortToken(): void
    {
        $this->client->request(
            'GET',
            '/pt_BR/api/reset-password/status/short',
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCheckStatusReturns410ForExpiredToken(): void
    {
        $user = $this->makeVerifiedUser('expired-status@example.com');
        $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        $this->expireResetRequestsOf($user);

        $this->client->request('GET', '/pt_BR/api/reset-password/status/' . $resetToken->getToken());

        $error = $this->assertApiError($this->client, Response::HTTP_GONE, 'expired_reset_token_error');
        $this->assertSame('request_new_token', $error['action_required']);
    }

    // ------------------------------------------------------------------
    // POST /reset-password/cancel
    // ------------------------------------------------------------------

    public function testCancelSucceedsForValidTokenAndInvalidatesIt(): void
    {
        $user = $this->makeVerifiedUser('cancelme@example.com');
        $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        $fullToken = $resetToken->getToken();

        $this->postJson($this->client, '/pt_BR/api/reset-password/cancel', ['token' => $fullToken]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSame(['cancelled' => true], $this->responseData($this->client)['data']);

        // Confirma que o cancelamento de fato invalidou o token: checar o
        // status agora deve falhar (não existe mais / não é mais válido).
        $this->client->request('GET', '/pt_BR/api/reset-password/status/' . $fullToken);
        $this->assertContains(
            $this->client->getResponse()->getStatusCode(),
            [Response::HTTP_BAD_REQUEST, Response::HTTP_NOT_FOUND],
        );
    }

    public function testCancelReturns404ForNonexistentToken(): void
    {
        $fakeToken = str_repeat('b', 40);

        $this->postJson($this->client, '/pt_BR/api/reset-password/cancel', ['token' => $fakeToken]);

        $this->assertApiError($this->client, Response::HTTP_NOT_FOUND, 'reset_token_not_found_error');
    }

    public function testCancelReturns400ForMissingTokenField(): void
    {
        $this->postJson($this->client, '/pt_BR/api/reset-password/cancel', []);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // ------------------------------------------------------------------
    // POST /reset-password/confirm (novo)
    // ------------------------------------------------------------------

    public function testConfirmChangesThePasswordAndTheNewOneLogsIn(): void
    {
        $user = $this->makeVerifiedUser('confirm@example.com');
        $fullToken = $this->resetPasswordHelper->generateResetToken($user)->getToken();

        $this->postJson($this->client, '/pt_BR/api/reset-password/confirm', [
            'token' => $fullToken,
            'password' => self::NEW_PASSWORD,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSame(['password_reset' => true], $this->responseData($this->client)['data']);

        $reloaded = static::getContainer()->get('doctrine')->getManager()
            ->getRepository(User::class)
            ->findByEmail('confirm@example.com');
        $this->assertTrue(
            static::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($reloaded, self::NEW_PASSWORD),
        );

        $this->postJson($this->client, '/api/login_check', [
            'email' => 'confirm@example.com',
            'password' => self::NEW_PASSWORD,
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('token', $this->responseData($this->client));
    }

    public function testConfirmTokenWorksOnlyOnce(): void
    {
        $user = $this->makeVerifiedUser('once@example.com');
        $fullToken = $this->resetPasswordHelper->generateResetToken($user)->getToken();

        $this->postJson($this->client, '/pt_BR/api/reset-password/confirm', [
            'token' => $fullToken,
            'password' => self::NEW_PASSWORD,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->postJson($this->client, '/pt_BR/api/reset-password/confirm', [
            'token' => $fullToken,
            'password' => 'AnotherSecurePassword789!',
        ]);
        $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, 'invalid_reset_token_error');
    }

    /**
     * Quem estava logado com a senha antiga não renova mais o JWT.
     */
    public function testConfirmRevokesTheRefreshTokensOfTheUser(): void
    {
        $user = $this->makeVerifiedUser('sessions@example.com');
        $otherUser = $this->makeVerifiedUser('other-sessions@example.com');

        $this->entityManager->persist(RefreshToken::createForUserWithTtl(str_repeat('r', 64), $user, 3600));
        $this->entityManager->persist(RefreshToken::createForUserWithTtl(str_repeat('o', 64), $otherUser, 3600));
        $this->entityManager->flush();

        $fullToken = $this->resetPasswordHelper->generateResetToken($user)->getToken();

        $this->postJson($this->client, '/pt_BR/api/reset-password/confirm', [
            'token' => $fullToken,
            'password' => self::NEW_PASSWORD,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $remaining = static::getContainer()->get('doctrine')->getManager()
            ->getRepository(RefreshToken::class)
            ->findAll();
        $this->assertSame(
            ['other-sessions@example.com'],
            array_map(static fn(RefreshToken $token): string => (string) $token->getUsername(), $remaining),
        );
    }

    public function testConfirmRejectsAWeakPasswordAndKeepsTheToken(): void
    {
        $user = $this->makeVerifiedUser('weak@example.com');
        $fullToken = $this->resetPasswordHelper->generateResetToken($user)->getToken();

        $this->postJson($this->client, '/pt_BR/api/reset-password/confirm', [
            'token' => $fullToken,
            'password' => '123',
        ]);

        $error = $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, 'user_validation_error');
        $this->assertStringContainsString('Password:', $error['errors'][0]);

        // A senha fraca não gasta o link.
        $this->client->request('GET', '/pt_BR/api/reset-password/status/' . $fullToken);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testConfirmRejectsAnUnknownToken(): void
    {
        $this->postJson($this->client, '/pt_BR/api/reset-password/confirm', [
            'token' => str_repeat('c', 40),
            'password' => self::NEW_PASSWORD,
        ]);

        $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, 'invalid_reset_token_error');
    }

    public function testConfirmRejectsAnExpiredToken(): void
    {
        $user = $this->makeVerifiedUser('expired-confirm@example.com');
        $fullToken = $this->resetPasswordHelper->generateResetToken($user)->getToken();
        $this->expireResetRequestsOf($user);

        $this->postJson($this->client, '/pt_BR/api/reset-password/confirm', [
            'token' => $fullToken,
            'password' => self::NEW_PASSWORD,
        ]);

        $this->assertApiError($this->client, Response::HTTP_GONE, 'expired_reset_token_error');
    }

    // ------------------------------------------------------------------
    // GET /reset-password/requests/{userId} (exige JWT)
    // ------------------------------------------------------------------

    public function testGetUserResetRequestsReturnsActiveRequests(): void
    {
        $user = $this->makeVerifiedUser('userrequests@example.com');
        $this->resetPasswordHelper->generateResetToken($user);
        $this->authenticateClient($this->client, $user);

        $this->client->request(
            'GET',
            '/pt_BR/api/reset-password/requests/' . $user->getId(),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = $this->responseData($this->client);
        $this->assertTrue($data['success']);
        $this->assertSame(['user_id', 'active_requests'], array_keys($data['data']));
        $this->assertSame($user->getId(), $data['data']['user_id']);
        $this->assertCount(1, $data['data']['active_requests']);
        $this->assertSame(['id', 'created_at', 'expires_at'], array_keys($data['data']['active_requests'][0]));
    }

    /**
     * IDOR: antes qualquer usuário logado via as solicitações de qualquer
     * outro.
     */
    public function testUserCannotListTheRequestsOfAnotherUser(): void
    {
        $victim = $this->makeVerifiedUser('victim@example.com');
        $this->resetPasswordHelper->generateResetToken($victim);
        $attacker = $this->makeVerifiedUser('attacker@example.com');
        $this->authenticateClient($this->client, $attacker);

        $this->client->request('GET', '/pt_BR/api/reset-password/requests/' . $victim->getId());

        $this->assertApiError($this->client, Response::HTTP_FORBIDDEN, 'permission_denied_error');
        $this->assertStringNotContainsString('active_requests', (string) $this->client->getResponse()->getContent());
    }

    public function testAdminCanListTheRequestsOfAnotherUser(): void
    {
        $user = $this->makeVerifiedUser('managed@example.com');
        $this->resetPasswordHelper->generateResetToken($user);

        $admin = UserTestBuilder::create()
            ->withEmail('admin-requests@example.com')
            ->withPassword('hashed_password_123')
            ->asAdmin()
            ->verified()
            ->build();
        $this->userRepository->save($admin);
        $this->authenticateClient($this->client, $admin);

        $this->client->request('GET', '/pt_BR/api/reset-password/requests/' . $user->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertCount(1, $this->responseData($this->client)['data']['active_requests']);
    }

    public function testGetUserResetRequestsReturns404ForUnknownUserAsAdmin(): void
    {
        $admin = UserTestBuilder::create()
            ->withEmail('admin-unknown@example.com')
            ->withPassword('hashed_password_123')
            ->asAdmin()
            ->verified()
            ->build();
        $this->userRepository->save($admin);
        $this->authenticateClient($this->client, $admin);

        $this->client->request('GET', '/pt_BR/api/reset-password/requests/999999');

        $this->assertApiError($this->client, Response::HTTP_NOT_FOUND, 'user_not_found_error');
    }

    public function testGetUserResetRequestsReturns400ForInvalidUserId(): void
    {
        $user = $this->makeVerifiedUser('invalid-user-id@example.com');
        $this->authenticateClient($this->client, $user);

        $this->client->request('GET', '/pt_BR/api/reset-password/requests/0');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetUserResetRequestsRequiresAuthentication(): void
    {
        $user = $this->makeVerifiedUser('anonymous-requests@example.com');

        $this->client->request(
            'GET',
            '/pt_BR/api/reset-password/requests/' . $user->getId(),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Vence as solicitações do usuário direto no banco (o tempo de vida
     * vem da configuração do bundle, 1 hora).
     */
    private function expireResetRequestsOf(User $user): void
    {
        $this->entityManager
            ->createQuery(
                'UPDATE ' . ResetPasswordSolicitation::class . ' r SET r.expiresAt = :past WHERE r.user = :user',
            )
            ->setParameter('past', new DateTimeImmutable('-1 minute'))
            ->setParameter('user', $user)
            ->execute();

        $this->entityManager->clear();
    }
}
