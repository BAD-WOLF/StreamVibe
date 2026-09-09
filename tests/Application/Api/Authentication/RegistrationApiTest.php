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
 * API tests for user registration endpoint
 *
 * Tests the complete registration flow through HTTP interface
 * following the testing pyramid principles for application tests.
 *
 * NOTA: RegisterUserRequest passou a exigir agreeTerms=true
 * (Assert\IsTrue, default false). Os payloads abaixo foram atualizados
 * para incluir esse campo sempre que o teste não é especificamente sobre
 * ele — do contrário toda validação falhava com "You must agree to the
 * terms and conditions" antes mesmo de exercitar o cenário pretendido.
 */
final class RegistrationApiTest extends WebTestCase
{
    use DatabaseTestTrait;

    private UserRepository $userRepository;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();
        $this->entityManager = $container->get("doctrine")->getManager();

        // UserRepository é um serviço privado (ServiceEntityRepository) e o
        // container de teste inlina/remove serviços privados não
        // referenciados diretamente, causando ServiceNotFoundException.
        // O EntityManager usa o localizador de repositórios interno do
        // Doctrine, que não depende de visibilidade pública no container.
        $this->userRepository = $this->entityManager->getRepository(User::class);

        $this->setupTestDatabase();
    }

    protected function tearDown(): void
    {
        $this->teardownTestDatabase();
        parent::tearDown();
    }

    public function testSuccessfulRegistration(): void
    {
        // Arrange
        $client = $this->client;
        $registrationData = [
            "email" => "newuser@example.com",
            "password" => "SecurePassword123!",
            "agreeTerms" => true,
        ];

        // Act
        $client->request(
            "POST",
            "/pt_BR/api/register",
            [],
            [],
            ["CONTENT_TYPE" => "application/json"],
            json_encode($registrationData),
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame("content-type", "application/json");

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey("message", $responseData);
        $this->assertStringContainsString(
            "registered successfully",
            $responseData["message"],
        );

        // Verify user was created in database
        $user = $this->userRepository->findByEmail("newuser@example.com");
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals("newuser@example.com", $user->getEmail());
        $this->assertFalse($user->isVerified());
        $this->assertNotEmpty($user->getPassword());

        // Verify confirmation email was sent
        $this->assertEmailCount(1);
        $messages = $this->getMailerMessages();
        $this->assertEmailAddressContains(
            $messages[0],
            "to",
            "newuser@example.com",
        );
        $this->assertEmailSubjectContains($messages[0], "confirm your email");
    }

    public function testRegistrationWithInvalidEmail(): void
    {
        // Arrange
        $client = $this->client;
        $registrationData = [
            "email" => "invalid-email",
            "password" => "SecurePassword123!",
            "agreeTerms" => true,
        ];

        // Act
        $client->request(
            "POST",
            "/pt_BR/api/register",
            [],
            [],
            ["CONTENT_TYPE" => "application/json"],
            json_encode($registrationData),
        );

        // Assert
        // NOTA: UserValidationException mapeia pra 400 no ExceptionListener
        // (handleUserValidationException: $exception->getCode() ?: 400),
        // não 422 — corrigido pra bater com o comportamento real.
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertEntityCount(User::class, 0);
        $this->assertEmailCount(0);
    }

    public function testRegistrationWithMissingEmail(): void
    {
        // Arrange
        $client = $this->client;
        $registrationData = [
            "password" => "SecurePassword123!",
            "agreeTerms" => true,
        ];

        // Act
        $client->request(
            "POST",
            "/pt_BR/api/register",
            [],
            [],
            ["CONTENT_TYPE" => "application/json"],
            json_encode($registrationData),
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertEntityCount(User::class, 0);
    }

    public function testRegistrationWithMissingPassword(): void
    {
        // Arrange
        $client = $this->client;
        $registrationData = [
            "email" => "test@example.com",
            "agreeTerms" => true,
        ];

        // Act
        $client->request(
            "POST",
            "/pt_BR/api/register",
            [],
            [],
            ["CONTENT_TYPE" => "application/json"],
            json_encode($registrationData),
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertEntityCount(User::class, 0);
    }

    public function testRegistrationWithWeakPassword(): void
    {
        // Arrange
        $client = $this->client;
        $registrationData = [
            "email" => "test@example.com",
            "password" => "123", // Too short
            "agreeTerms" => true,
        ];

        // Act
        $client->request(
            "POST",
            "/pt_BR/api/register",
            [],
            [],
            ["CONTENT_TYPE" => "application/json"],
            json_encode($registrationData),
        );

        // Assert
        // NOTA: mesmo motivo do teste de email inválido — 400, não 422.
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertEntityCount(User::class, 0);
    }

    public function testRegistrationWithDuplicateEmail(): void
    {
        // Arrange - Create existing user using builder
        $existingUser = UserTestBuilder::create()
                                       ->withEmail("existing@example.com")
                                       ->withPassword("hashed_password")
                                       ->verified()
                                       ->build();

        $this->userRepository->save($existingUser);

        $client = $this->client;
        $registrationData = [
            "email" => "existing@example.com", // Same email
            "password" => "NewPassword123!",
            "agreeTerms" => true,
        ];

        // Act
        $client->request(
            "POST",
            "/pt_BR/api/register",
            [],
            [],
            ["CONTENT_TYPE" => "application/json"],
            json_encode($registrationData),
        );

        // Assert
        // NOTA: UserAlreadyExistsException mapeia pra 409 Conflict no
        // ExceptionListener (handleUserAlreadyExistsException:
        // $exception->getCode() ?: 409) — mais correto semanticamente que
        // 422 pra "recurso já existe". Corrigido pra bater com o real.
        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        $this->assertEntityCount(User::class, 1);

        // Verify original user remains unchanged
        $users = $this->userRepository->findAll();
        $this->assertTrue($users[0]->isVerified());
    }

    public function testRegistrationWithEmptyPayload(): void
    {
        // Arrange
        $client = $this->client;

        // Act
        $client->request(
            "POST",
            "/pt_BR/api/register",
            [],
            [],
            ["CONTENT_TYPE" => "application/json"],
            "{}",
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertEntityCount(User::class, 0);
    }

    public function testRegistrationWithMalformedJson(): void
    {
        // Arrange
        $client = $this->client;

        // Act
        $client->request(
            "POST",
            "/pt_BR/api/register",
            [],
            [],
            ["CONTENT_TYPE" => "application/json"],
            '{"email": "test@example.com", "password": "password"', // Malformed JSON
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertEntityCount(User::class, 0);
    }

    public function testRegistrationWithWrongContentType(): void
    {
        // Arrange
        $client = $this->client;

        // Act - Send form data instead of JSON
        $client->request("POST", "/pt_BR/api/register", [
            "email" => "test@example.com",
            "password" => "SecurePassword123!",
            "agreeTerms" => true,
        ]);

        // Assert - Should handle form data gracefully or return appropriate error
        $response = $client->getResponse();
        $this->assertTrue(
            $response->getStatusCode() === Response::HTTP_BAD_REQUEST ||
            $response->getStatusCode() ===
            Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
        );
    }

    public function testRegistrationResponseFormat(): void
    {
        // Arrange
        $client = $this->client;
        $registrationData = [
            "email" => "format@example.com",
            "password" => "SecurePassword123!",
            "agreeTerms" => true,
        ];

        // Act
        $client->request(
            "POST",
            "/pt_BR/api/register",
            [],
            [],
            ["CONTENT_TYPE" => "application/json"],
            json_encode($registrationData),
        );

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame("content-type", "application/json");

        $responseData = json_decode($client->getResponse()->getContent(), true);

        // Verify response structure
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey("message", $responseData);
        $this->assertIsString($responseData["message"]);
        $this->assertNotEmpty($responseData["message"]);
    }

    public function testRegistrationWithSpecialCharactersInEmail(): void
    {
        // Arrange
        $client = $this->client;
        $registrationData = [
            "email" => "user+test@example.com", // Valid email with special chars
            "password" => "SecurePassword123!",
            "agreeTerms" => true,
        ];

        // Act
        $client->request(
            "POST",
            "/pt_BR/api/register",
            [],
            [],
            ["CONTENT_TYPE" => "application/json"],
            json_encode($registrationData),
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $user = $this->userRepository->findByEmail("user+test@example.com");
        $this->assertNotNull($user);
        $this->assertEquals("user+test@example.com", $user->getEmail());
    }

    public function testRegistrationValidatesEmailLength(): void
    {
        // Arrange - Email too long
        $longEmail = str_repeat("a", 250) . "@example.com";
        $client = $this->client;
        $registrationData = [
            "email" => $longEmail,
            "password" => "SecurePassword123!",
            "agreeTerms" => true,
        ];

        // Act
        $client->request(
            "POST",
            "/pt_BR/api/register",
            [],
            [],
            ["CONTENT_TYPE" => "application/json"],
            json_encode($registrationData),
        );

        // Assert
        // NOTA: mesmo motivo dos outros casos de UserValidationException — 400, não 422.
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertEntityCount(User::class, 0);
    }

    public function testRegistrationHandlesUnicodeCharacters(): void
    {
        // Arrange
        $client = $this->client;
        $registrationData = [
            "email" => "tëst@éxãmplê.com",
            "password" => "SecurePassword123!",
            "agreeTerms" => true,
        ];

        // Act
        $client->request(
            "POST",
            "/pt_BR/api/register",
            [],
            [],
            ["CONTENT_TYPE" => "application/json"],
            json_encode($registrationData, JSON_UNESCAPED_UNICODE),
        );

        // Assert - Should either accept or reject gracefully
        // NOTA: mesmo motivo dos outros — UserValidationException é 400, não 422.
        $response = $client->getResponse();
        $this->assertTrue(
            $response->getStatusCode() === Response::HTTP_CREATED ||
            $response->getStatusCode() === Response::HTTP_BAD_REQUEST,
        );

        if ($response->getStatusCode() === Response::HTTP_CREATED) {
            $user = $this->userRepository->findByEmail("tëst@éxãmplê.com");
            $this->assertNotNull($user);
        }
    }

    public function testRegistrationPerformanceWithValidData(): void
    {
        // Arrange
        $client = $this->client;
        $registrationData = [
            "email" => "performance@example.com",
            "password" => "SecurePassword123!",
            "agreeTerms" => true,
        ];

        // Act - Measure registration time
        $start = microtime(true);

        $client->request(
            "POST",
            "/pt_BR/api/register",
            [],
            [],
            ["CONTENT_TYPE" => "application/json"],
            json_encode($registrationData),
        );

        $end = microtime(true);
        $duration = $end - $start;

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertLessThan(
            2.0,
            $duration,
            "Registration should complete within 2 seconds",
        );

        // Verify user was created
        $user = $this->userRepository->findByEmail("performance@example.com");
        $this->assertNotNull($user);
    }
}

