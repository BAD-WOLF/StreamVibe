<?php

declare(strict_types=1);

namespace App\Tests\Application\Api\Authentication;

use App\Domain\User\Entity\User;
use App\Infrastructure\Persistence\Repository\UserRepository;
use App\Tests\Helper\DatabaseTestTrait;
use App\Tests\Helper\UserTestBuilder;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * API tests for password reset endpoints (solicitation, check-status,
 * cancel, user-requests) through HTTP real, no mock.
 *
 * NOTA: até esta suíte, nenhum destes 4 endpoints tinha teste de API —
 * só existia teste unitário do ResetUserPasswordUseCase isolado, que
 * nunca passa pelo controller. Foi exatamente essa lacuna que deixou
 * passar um bug crítico (ResetPasswordSolicitationController chamando
 * um método inexistente no UseCase, quebrando 100% das solicitações de
 * reset em produção) até ser achado manualmente no Swagger.
 *
 * NOTA 2: para os testes de check-status/cancel, geramos o token real
 * direto via ResetPasswordHelperInterface do container, em vez de tentar
 * extrair da URL dentro do corpo do email (não temos o template
 * reset_password/email.html.twig para confirmar o formato do link, e já
 * levamos uma lição de não chutar formato de rota/URL sem confirmar).
 * ResetPasswordHelperInterface::generateResetToken() é o mesmo mecanismo
 * que o UseCase usa internamente — ResetPasswordToken::getToken() devolve
 * o token completo (selector + verificador) em texto puro, documentado
 * pelo próprio bundle SymfonyCasts.
 */
final class ResetPasswordApiTest extends WebTestCase
{
    use DatabaseTestTrait;

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

        $this->client->request(
            'POST',
            '/pt_BR/api/reset-password',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'resetme@example.com']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['email_sent']);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('expires_at', $data);

        $this->assertEmailCount(1);
    }

    public function testSolicitationReturnsGenericResponseForNonexistentEmail(): void
    {
        // NOTA: por segurança, o UseCase não revela se o email existe —
        // retorna 200 genérico tanto para email existente quanto
        // inexistente (ver ResetUserPasswordUseCase::execute(),
        // "returnsGenericSuccessWhenUserDoesNotExist" já coberto no teste
        // unitário). O controller até tem um catch(UserNotFoundException)
        // mapeando pra 404, mas esse branch nunca é alcançado — o UseCase
        // nunca lança essa exceção nesse fluxo.
        $this->client->request(
            'POST',
            '/pt_BR/api/reset-password',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'ghost-nobody@example.com']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($data['email_sent']);

        $this->assertEmailCount(0);
    }

    public function testSolicitationFailsForUnverifiedUser(): void
    {
        $user = UserTestBuilder::create()
            ->withEmail('unverified-reset@example.com')
            ->withPassword('hashed_password_123')
            ->build(); // não chama ->verified()

        $this->userRepository->save($user);

        $this->client->request(
            'POST',
            '/pt_BR/api/reset-password',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'unverified-reset@example.com']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertEmailCount(0);
    }

    public function testSolicitationFailsWithMissingEmail(): void
    {
        $this->client->request(
            'POST',
            '/pt_BR/api/reset-password',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testSolicitationFailsWithInvalidBody(): void
    {
        $this->client->request(
            'POST',
            '/pt_BR/api/reset-password',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'not a json object',
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testSolicitationReturnsExistingRequestWhenActiveRequestExists(): void
    {
        $user = $this->makeVerifiedUser('already-active@example.com');

        // Gera uma solicitação ativa de verdade antes da chamada HTTP,
        // pra exercitar o branch "já existe solicitação ativa" do UseCase.
        $this->resetPasswordHelper->generateResetToken($user);

        $this->client->request(
            'POST',
            '/pt_BR/api/reset-password',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'already-active@example.com']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        // Reaproveita a solicitação existente — não dispara novo email.
        $this->assertFalse($data['email_sent']);

        $this->assertEmailCount(0);
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

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['valid']);
        $this->assertFalse($data['expired']);
        $this->assertFalse($data['used']);
    }

    public function testCheckStatusReturns400ForNonexistentToken(): void
    {
        // Token bem formado (>=20 chars) mas que nunca foi gerado.
        $fakeToken = str_repeat('a', 40);

        $this->client->request(
            'GET',
            '/pt_BR/api/reset-password/status/' . $fakeToken,
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCheckStatusReturns400ForTooShortToken(): void
    {
        $this->client->request(
            'GET',
            '/pt_BR/api/reset-password/status/short',
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // ------------------------------------------------------------------
    // POST /reset-password/cancel
    // ------------------------------------------------------------------

    public function testCancelSucceedsForValidTokenAndInvalidatesIt(): void
    {
        $user = $this->makeVerifiedUser('cancelme@example.com');
        $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        $fullToken = $resetToken->getToken();

        $this->client->request(
            'POST',
            '/pt_BR/api/reset-password/cancel',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => $fullToken]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

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

        $this->client->request(
            'POST',
            '/pt_BR/api/reset-password/cancel',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => $fakeToken]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCancelReturns400ForMissingTokenField(): void
    {
        $this->client->request(
            'POST',
            '/pt_BR/api/reset-password/cancel',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // ------------------------------------------------------------------
    // GET /reset-password/requests/{userId}
    // ------------------------------------------------------------------

    public function testGetUserResetRequestsReturnsActiveRequests(): void
    {
        $user = $this->makeVerifiedUser('userrequests@example.com');
        $this->resetPasswordHelper->generateResetToken($user);

        $this->client->request(
            'GET',
            '/pt_BR/api/reset-password/requests/' . $user->getId(),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertSame($user->getId(), $data['data']['user_id']);
        $this->assertGreaterThanOrEqual(1, $data['data']['count']);
        $this->assertCount($data['data']['count'], $data['data']['active_requests']);
    }

    public function testGetUserResetRequestsReturns400ForInvalidUserId(): void
    {
        $this->client->request('GET', '/pt_BR/api/reset-password/requests/0');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
    }
