<?php

declare(strict_types=1);

namespace App\Tests\Application\Api\Authentication;

use App\Domain\User\Entity\User;
use App\Infrastructure\Persistence\Repository\UserRepository;
use App\Tests\Helper\ApiRequestTrait;
use App\Tests\Helper\DatabaseTestTrait;
use App\Tests\Helper\UserTestBuilder;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * API tests for POST /{locale}/api/resend-verification (public).
 *
 * NOTA (out/2026): o reenvio não tinha teste nenhum. Ele rodava dentro do
 * RegisterUserUseCase por uma flag, e o outro reenvio
 * (POST /api/verify/email/resend, que devolvia o link de verificação na
 * resposta) saiu. testTheLinkIsNotInTheResponse() garante que o link só
 * vai por email.
 */
final class ResendVerificationApiTest extends WebTestCase
{
    use DatabaseTestTrait;
    use ApiRequestTrait;

    private const string ENDPOINT = "/pt_BR/api/resend-verification";

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

    public function testUnverifiedAccountGetsANewEmail(): void
    {
        $this->makeUser("resend-me@example.com", verified: false);

        $this->postJson($this->client, self::ENDPOINT, ["email" => "resend-me@example.com"]);

        $this->assertResponseIsSuccessful();
        $data = $this->responseData($this->client);
        $this->assertSame(["success", "data", "message"], array_keys($data));
        $this->assertSame(["email_sent" => true], $data["data"]);

        $this->assertEmailCount(1);
        $email = $this->getMailerMessages()[0];
        $this->assertEmailAddressContains($email, "to", "resend-me@example.com");
        $this->assertEmailHtmlBodyContains($email, "/pt_BR/api/verify/email?");
    }

    public function testTheLinkIsNotInTheResponse(): void
    {
        $this->makeUser("no-link@example.com", verified: false);

        $this->postJson($this->client, self::ENDPOINT, ["email" => "no-link@example.com"]);

        $this->assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString("verify/email", $content);
        $this->assertStringNotContainsString("signature", $content);
    }

    public function testUnknownEmailAnswersTheGenericMessage(): void
    {
        $this->postJson($this->client, self::ENDPOINT, ["email" => "nobody@example.com"]);

        $this->assertResponseIsSuccessful();
        $data = $this->responseData($this->client);
        $this->assertSame(["email_sent" => false], $data["data"]);
        $this->assertStringContainsString("If an account with this email exists", $data["message"]);
        $this->assertEmailCount(0);
    }

    public function testVerifiedAccountIsAConflict(): void
    {
        $this->makeUser("verified@example.com", verified: true);

        $this->postJson($this->client, self::ENDPOINT, ["email" => "verified@example.com"]);

        $this->assertApiError($this->client, Response::HTTP_CONFLICT, "user_already_exists_error");
        $this->assertEmailCount(0);
    }

    public function testInvalidEmailIsA400(): void
    {
        $this->postJson($this->client, self::ENDPOINT, ["email" => "not-an-email"]);

        $error = $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, "user_validation_error");
        $this->assertSame(["Email: Please provide a valid email address"], $error["errors"]);
    }

    /**
     * Antes: trim() de um inteiro com strict_types — TypeError, 500.
     */
    public function testEmailThatIsNotTextIsA400(): void
    {
        $this->postJson($this->client, self::ENDPOINT, ["email" => 123]);

        $error = $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, "user_validation_error");
        $this->assertSame(["Email: This value should be of type string."], $error["errors"]);
    }

    public function testMalformedJsonIsA400(): void
    {
        $this->postJson($this->client, self::ENDPOINT, '{"email": ');

        $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, "user_validation_error");
    }

    private function makeUser(string $email, bool $verified): User
    {
        $user = UserTestBuilder::create()
            ->withEmail($email)
            ->withPassword("hashed_password_123")
            ->verified($verified)
            ->build();

        $this->userRepository->save($user);

        return $user;
    }
}
