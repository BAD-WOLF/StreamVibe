<?php

declare(strict_types=1);

namespace App\Tests\Application\Api\Authentication;

use App\Domain\User\Entity\User;
use App\Infrastructure\Persistence\Repository\UserRepository;
use App\Tests\Helper\DatabaseTestTrait;
use App\Tests\Helper\UserTestBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * End-to-end test for the complete user registration flow
 *
 * NOTA 1: RegisterUserRequest exige agreeTerms=true (Assert\IsTrue, default
 * false). Payloads atualizados para incluir esse campo.
 *
 * NOTA 2: RegisterUserUseCase::sendVerificationEmail() usa
 * 'noreply@streamvibe.com' (StreamVibe) como remetente — confirmado via
 * teste real.
 *
 * NOTA 3: UserAlreadyExistsException só é lançada quando o usuário
 * existente JÁ está verificado (409 Conflict). Se o usuário existe mas não
 * está verificado, RegisterUserUseCase reenvia o email de verificação e
 * retorna sucesso (201) — comportamento real do UseCase, não bug de teste.
 *
 * NOTA 4: templates/registration/confirmation_email.html.twig não contém
 * "StreamVibe" em lugar nenhum, e o texto de expiração do link vem de
 * {{ expiresAtMessageKey|trans(...) }} (tradução dinâmica do
 * VerifyEmailBundle, não texto fixo) — asserções ajustadas para verificar
 * apenas conteúdo literal e garantido do template.
 *
 * NOTA 5: assertEmailCount() reflete apenas os emails da última requisição
 * (o KernelBrowser reinicia o kernel a cada $client->request()), não um
 * total acumulado entre requisições — corrigido no teste de reenvio.
 *
 * NOTA 6: A rota de verificação é registrada com path fixo
 * "/api/verify/email" (ver EmailVerificationController::verifyUserEmail,
 * #[Route(path: "/api/verify/email", name: "api_verify_email")]) — o
 * regex de extração da URL foi ajustado para aceitar qualquer prefixo de
 * path antes de "verify/email", não apenas logo após o host.
 *
 * NOTA 7: $client->request() reinicializa o kernel por padrão, criando um
 * novo EntityManager no container a cada chamada. Isso torna
 * DatabaseTestTrait::refreshEntity() inseguro para fluxos multi-requisição
 * (ex.: registrar -> verificar): a instância de entidade carregada antes
 * do reboot não é mais reconhecida como "gerenciada" pelo EM real depois
 * dele. Em vez de usar refreshEntity(), buscamos a entidade do zero via o
 * container atual após a segunda requisição.
 */
final class UserRegistrationFlowTest extends WebTestCase
{
    use DatabaseTestTrait;

    private UserRepository $userRepository;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();
        $this->entityManager = $container->get("doctrine")->getManager();

        $this->userRepository = $this->entityManager->getRepository(User::class);

        $this->setupTestDatabase();
    }

    protected function tearDown(): void
    {
        $this->teardownTestDatabase();
        parent::tearDown();
    }

    public function testCompleteRegistrationAndVerificationFlow(): void
    {
        $client = $this->client;
        $userEmail = "testuser@streamvibe.com";
        $userPassword = "SecureTestPassword123!";

        $registrationData = [
            "email" => $userEmail,
            "password" => $userPassword,
            "agreeTerms" => true,
        ];

        $client->request(
            "POST",
            "/pt_BR/api/register",
            [],
            [],
            ["CONTENT_TYPE" => "application/json"],
            json_encode($registrationData),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey("message", $responseData);
        $this->assertStringContainsString(
            "registered successfully",
            $responseData["message"],
        );

        $this->assertEntityCount(User::class, 1);

        $user = $this->userRepository->findByEmail($userEmail);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($userEmail, $user->getEmail());
        $this->assertFalse($user->isVerified());
        $this->assertNotEmpty($user->getPassword());

        $this->assertEmailCount(1);
        $messages = $this->getMailerMessages();
        $confirmationEmail = $messages[0];

        $this->assertEmailAddressContains(
            $confirmationEmail,
            "from",
            "noreply@streamvibe.com",
        );
        $this->assertEmailAddressContains($confirmationEmail, "to", $userEmail);
        $this->assertEmailTextBodyContains(
            $confirmationEmail,
            "This link will expire in",
        );

        $verificationUrl = $this->extractVerificationUrlFromEmail(
            $confirmationEmail,
        );
        $client->request("GET", $verificationUrl);
        $this->assertResponseIsSuccessful();

        // Ver NOTA 7: buscamos o usuário do zero via o container atual em
        // vez de refreshEntity($user), que quebra após o reboot do kernel.
        $currentContainer = static::getContainer();
        $currentEntityManager = $currentContainer->get("doctrine")->getManager();
        $currentUserRepository = $currentEntityManager->getRepository(User::class);

        $verifiedUser = $currentUserRepository->findByEmail($userEmail);
        $this->assertInstanceOf(User::class, $verifiedUser);
        $this->assertTrue($verifiedUser->isVerified());
    }

    public function testRegistrationWithExistingEmailFails(): void
    {
        $existingEmail = "existing@streamvibe.com";
        $existingUser = UserTestBuilder::create()
                                       ->withEmail($existingEmail)
                                       ->withPassword("existing_password_hash")
                                       ->verified()
                                       ->build();

        $this->userRepository->save($existingUser);

        $client = $this->client;
        $registrationData = [
            "email" => $existingEmail,
            "password" => "NewPassword123!",
            "agreeTerms" => true,
        ];

        $client->request(
            "POST",
            "/pt_BR/api/register",
            [],
            [],
            ["CONTENT_TYPE" => "application/json"],
            json_encode($registrationData),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        $this->assertEntityCount(User::class, 1);

        $users = $this->userRepository->findAll();
        $this->assertTrue($users[0]->isVerified());
        $this->assertEmailCount(0);
    }

    public function testRegistrationValidationHandlesInvalidData(): void
    {
        $client = $this->client;

        $invalidCases = [
            [
                "data" => [
                    "email" => "invalid-email",
                    "password" => "ValidPass123!",
                    "agreeTerms" => true,
                ],
                "description" => "invalid email format",
            ],
            [
                "data" => [
                    "email" => "valid@email.com",
                    "password" => "123",
                    "agreeTerms" => true,
                ],
                "description" => "password too short",
            ],
            [
                "data" => [
                    "email" => "",
                    "password" => "ValidPass123!",
                    "agreeTerms" => true,
                ],
                "description" => "empty email",
            ],
            [
                "data" => [
                    "email" => "valid@email.com",
                    "password" => "",
                    "agreeTerms" => true,
                ],
                "description" => "empty password",
            ],
        ];

        foreach ($invalidCases as $case) {
            $client = $this->client;
            $client->request(
                "POST",
                "/pt_BR/api/register",
                [],
                [],
                ["CONTENT_TYPE" => "application/json"],
                json_encode($case["data"]),
            );

            $this->assertResponseStatusCodeSame(
                Response::HTTP_BAD_REQUEST,
                "Failed validation test for: " . $case["description"],
            );
            $this->assertEntityCount(User::class, 0);
        }
    }

    public function testRegistrationAssignsDefaultUserRole(): void
    {
        $client = $this->client;
        $registrationData = [
            "email" => "roletest@streamvibe.com",
            "password" => "TestPassword123!",
            "agreeTerms" => true,
        ];

        $client->request(
            "POST",
            "/pt_BR/api/register",
            [],
            [],
            ["CONTENT_TYPE" => "application/json"],
            json_encode($registrationData),
        );

        $this->assertResponseIsSuccessful();

        $user = $this->userRepository->findByEmail("roletest@streamvibe.com");
        $this->assertInstanceOf(User::class, $user);

        $roles = $user->getRoles();
        $this->assertContains("ROLE_USER", $roles);
        $this->assertCount(1, $roles);
    }

    /**
     * Como o usuário criado na primeira chamada nunca é verificado, a
     * segunda tentativa não é um "conflito de email duplicado" — é o
     * fluxo de reenvio de verificação do RegisterUserUseCase. Ambas as
     * chamadas devem suceder (201), sem criar um segundo registro no
     * banco. assertEmailCount(1) é checado após CADA requisição
     * individualmente, não como total acumulado.
     */
    public function testRepeatedRegistrationWithSameUnverifiedEmailResendsVerification(): void
    {
        $client = $this->client;
        $registrationData = [
            "email" => "multitest@streamvibe.com",
            "password" => "TestPassword123!",
            "agreeTerms" => true,
        ];

        // Primeira tentativa: cria o usuário
        $client->request(
            "POST",
            "/pt_BR/api/register",
            [],
            [],
            ["CONTENT_TYPE" => "application/json"],
            json_encode($registrationData),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertEmailCount(1);
        $this->assertEntityCount(User::class, 1);

        // Segunda tentativa: usuário existe mas não verificado -> reenvia.
        $client = $this->client;
        $client->request(
            "POST",
            "/pt_BR/api/register",
            [],
            [],
            ["CONTENT_TYPE" => "application/json"],
            json_encode($registrationData),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertEntityCount(User::class, 1); // não duplica o usuário
        $this->assertEmailCount(1); // email desta segunda requisição (reenvio)
    }

    public function testRegistrationEmailContentIsCorrect(): void
    {
        $client = $this->client;
        $userEmail = "emailcontent@streamvibe.com";
        $registrationData = [
            "email" => $userEmail,
            "password" => "TestPassword123!",
            "agreeTerms" => true,
        ];

        $client->request(
            "POST",
            "/pt_BR/api/register",
            [],
            [],
            ["CONTENT_TYPE" => "application/json"],
            json_encode($registrationData),
        );

        $this->assertResponseIsSuccessful();
        $this->assertEmailCount(1);

        $messages = $this->getMailerMessages();
        $email = $messages[0];

        $this->assertEmailAddressContains(
            $email,
            "from",
            "noreply@streamvibe.com",
        );
        $this->assertEmailAddressContains($email, "to", $userEmail);
        $this->assertEmailSubjectContains($email, "confirm your email");
        $this->assertEmailHtmlBodyContains($email, "Confirm my Email");
        $this->assertEmailTextBodyContains($email, "This link will expire in");

        $htmlBody = $email->getHtmlBody();
        $this->assertIsString($htmlBody);
        $this->assertStringContainsString("verify/email", $htmlBody);
    }

    /**
     * Sem verificação entre as tentativas, todas sucedem (201) via reenvio.
     * Validamos que não há duplicação de registro no banco, que é a
     * garantia de integridade real que importa aqui.
     */
    public function testRepeatedConcurrentRegistrationDoesNotDuplicateUser(): void
    {
        $email = "concurrent@streamvibe.com";
        $registrationData = [
            "email" => $email,
            "password" => "TestPassword123!",
            "agreeTerms" => true,
        ];

        $clients = [$this->client, $this->client, $this->client];

        $responses = [];
        foreach ($clients as $client) {
            $client->request(
                "POST",
                "/pt_BR/api/register",
                [],
                [],
                ["CONTENT_TYPE" => "application/json"],
                json_encode($registrationData),
            );
            $responses[] = $client->getResponse()->getStatusCode();
        }

        $successCount = count(
            array_filter(
                $responses,
                fn($code) => $code === Response::HTTP_CREATED,
            ),
        );

        $this->assertEquals(
            3,
            $successCount,
            "All attempts succeed while the user remains unverified (resend behavior)",
        );
        $this->assertEntityCount(User::class, 1);
    }

    /**
     * Extract verification URL from email content
     */
    private function extractVerificationUrlFromEmail($email): string
    {
        $messageBody = $email->getHtmlBody();
        $this->assertIsString($messageBody);

        // Path real da rota é "/api/verify/email" (ver
        // EmailVerificationController::verifyUserEmail, #[Route(path:
        // "/api/verify/email", name: "api_verify_email")]). O regex
        // aceita qualquer prefixo de path antes de "verify/email" em vez
        // de exigir que apareça logo após o host.
        preg_match(
            '#https?://[^/]+(?:/[^/]+)*/verify/email\?[^"]+#',
            $messageBody,
            $matches,
        );
        $this->assertNotEmpty($matches, "Verification URL not found in email");

        $verificationUrl = $matches[0];

        $query = parse_url($verificationUrl, PHP_URL_QUERY);
        parse_str($query, $params);

        $requiredParams = ["expires", "id", "signature", "token"];
        foreach ($requiredParams as $param) {
            $this->assertArrayHasKey(
                $param,
                $params,
                "Missing parameter: {$param}",
            );
        }

        return $verificationUrl;
    }
}

