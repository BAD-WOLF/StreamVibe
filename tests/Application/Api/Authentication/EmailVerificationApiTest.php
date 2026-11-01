<?php

declare(strict_types=1);

namespace App\Tests\Application\Api\Authentication;

use App\Domain\User\Entity\User;
use App\Infrastructure\ApiPlatform\Authentication\EmailVerification\Verify\VerifyEmailEntryPoint;
use App\Infrastructure\Persistence\Repository\UserRepository;
use App\Tests\Helper\ApiRequestTrait;
use App\Tests\Helper\DatabaseTestTrait;
use App\Tests\Helper\UserTestBuilder;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * API tests for the email verification endpoints (all public):
 *
 * - GET /{locale}/api/verify/email?expires=&id=&signature=&token=
 * - GET /{locale}/api/verify/email/status/{userId}
 * - GET /{locale}/api/verify/email/validate?expires=&id=&signature=&token=
 *
 * O link é gerado com o mesmo VerifyEmailHelper que o EmailVerifier usa,
 * passando o _locale (fora de uma requisição o router não tem um locale
 * no contexto).
 *
 * NOTA (out/2026): até aqui só o UserRegistrationFlowTest abria o link (o
 * caminho feliz). Nenhum teste cobria link adulterado, usuário
 * inexistente — que respondia 500, porque o catch (Exception) do
 * EmailVerificationController capturava o próprio UserNotFoundException —,
 * a consulta de status (que devolvia o email do usuário) nem a
 * conferência do link (que respondia sempre valid=false).
 */
final class EmailVerificationApiTest extends WebTestCase
{
    use DatabaseTestTrait;
    use ApiRequestTrait;

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

    // ------------------------------------------------------------------
    // GET /verify/email
    // ------------------------------------------------------------------

    public function testValidLinkVerifiesTheEmail(): void
    {
        $user = $this->makeUser("verify-me@example.com", verified: false);

        $this->client->request("GET", $this->signedLink($user));

        $this->assertResponseIsSuccessful();
        $data = $this->responseData($this->client);
        $this->assertTrue($data["success"]);
        $this->assertSame(
            ["user_id" => $user->getId(), "already_verified" => false, "verified" => true],
            $data["data"],
        );
        $this->assertSame("Email verified successfully", $data["message"]);
        $this->assertTrue($this->reloadUser("verify-me@example.com")->isVerified());
    }

    public function testOpeningTheLinkAgainIsNotAnError(): void
    {
        $user = $this->makeUser("already@example.com", verified: true);

        $this->client->request("GET", $this->signedLink($user));

        $this->assertResponseIsSuccessful();
        $data = $this->responseData($this->client);
        $this->assertTrue($data["data"]["already_verified"]);
        $this->assertTrue($data["data"]["verified"]);
    }

    public function testTamperedLinkIsA400AndDoesNotVerify(): void
    {
        $user = $this->makeUser("tampered@example.com", verified: false);

        $this->client->request("GET", $this->tamper($this->signedLink($user)));

        $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, "invalid_verification_link_error");
        $this->assertFalse($this->reloadUser("tampered@example.com")->isVerified());
    }

    public function testLinkOfAnotherLocaleDoesNotMatchTheSignature(): void
    {
        $user = $this->makeUser("locale@example.com", verified: false);

        // A assinatura cobre o endereço inteiro, inclusive o /pt_BR.
        $this->client->request("GET", str_replace("/pt_BR/", "/en/", $this->signedLink($user)));

        $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, "invalid_verification_link_error");
    }

    /**
     * Antes: 500 (o catch (Exception) do controller capturava o
     * UserNotFoundException).
     */
    public function testLinkOfAnUnknownUserIsA404(): void
    {
        $link = static::getContainer()->get(VerifyEmailHelperInterface::class)->generateSignature(
            VerifyEmailEntryPoint::ROUTE_NAME,
            "999999",
            "ghost@example.com",
            ["id" => 999999, "_locale" => "pt_BR"],
        )->getSignedUrl();

        $this->client->request("GET", $link);

        $this->assertApiError($this->client, Response::HTTP_NOT_FOUND, "user_not_found_error");
    }

    public function testMissingIdIsA400(): void
    {
        $this->client->request("GET", "/pt_BR/api/verify/email");

        $error = $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, "user_validation_error");
        $this->assertSame(["User_id: User ID must be a positive integer"], $error["errors"]);
    }

    public function testNonNumericIdIsA400(): void
    {
        $this->client->request("GET", "/pt_BR/api/verify/email?id=abc");

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertFalse($this->responseData($this->client)["success"]);
    }

    // ------------------------------------------------------------------
    // GET /verify/email/status/{userId}
    // ------------------------------------------------------------------

    public function testStatusTellsWhetherTheAccountIsVerified(): void
    {
        $unverified = $this->makeUser("pending@example.com", verified: false);
        $verified = $this->makeUser("done@example.com", verified: true);

        $this->client->request("GET", "/pt_BR/api/verify/email/status/" . $unverified->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSame(
            ["user_id" => $unverified->getId(), "verified" => false],
            $this->responseData($this->client)["data"],
        );

        $this->client->request("GET", "/pt_BR/api/verify/email/status/" . $verified->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSame(
            ["user_id" => $verified->getId(), "verified" => true],
            $this->responseData($this->client)["data"],
        );
    }

    /**
     * A rota é pública: com o email na resposta, percorrer os ids
     * listava os emails cadastrados.
     */
    public function testStatusDoesNotExposeTheEmail(): void
    {
        $user = $this->makeUser("secret-address@example.com", verified: false);

        $this->client->request("GET", "/pt_BR/api/verify/email/status/" . $user->getId());

        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString(
            "secret-address@example.com",
            (string) $this->client->getResponse()->getContent(),
        );
    }

    public function testStatusOfAnUnknownUserIsA404(): void
    {
        $this->client->request("GET", "/pt_BR/api/verify/email/status/999999");

        $this->assertApiError($this->client, Response::HTTP_NOT_FOUND, "user_not_found_error");
    }

    public function testStatusOfUserZeroIsA400(): void
    {
        $this->client->request("GET", "/pt_BR/api/verify/email/status/0");

        $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, "user_validation_error");
    }

    // ------------------------------------------------------------------
    // GET /verify/email/validate
    // ------------------------------------------------------------------

    /**
     * Antes respondia sempre valid=false: conferia a URL da própria
     * consulta (/verify/email/validate), não a do link.
     */
    public function testValidateConfirmsAGoodLinkWithoutVerifying(): void
    {
        $user = $this->makeUser("validate@example.com", verified: false);

        $this->client->request("GET", "/pt_BR/api/verify/email/validate?" . $this->queryOf($this->signedLink($user)));

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            ["user_id" => $user->getId(), "valid" => true, "already_verified" => false],
            $this->responseData($this->client)["data"],
        );
        $this->assertFalse($this->reloadUser("validate@example.com")->isVerified());
    }

    public function testValidateReportsATamperedLinkAsInvalid(): void
    {
        $user = $this->makeUser("validate-bad@example.com", verified: false);

        $this->client->request(
            "GET",
            "/pt_BR/api/verify/email/validate?" . $this->queryOf($this->tamper($this->signedLink($user))),
        );

        // Consulta, não ação: link inválido é 200 com valid=false.
        $this->assertResponseIsSuccessful();
        $this->assertFalse($this->responseData($this->client)["data"]["valid"]);
    }

    public function testValidateIgnoresALocaleInTheQuery(): void
    {
        $user = $this->makeUser("validate-locale@example.com", verified: false);

        $this->client->request(
            "GET",
            "/pt_BR/api/verify/email/validate?" . $this->queryOf($this->signedLink($user)) . "&_locale=xx",
        );

        $this->assertResponseIsSuccessful();
        $this->assertTrue($this->responseData($this->client)["data"]["valid"]);
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

    /**
     * Busca o usuário no container atual: o KernelBrowser reinicia o
     * kernel a cada requisição (ver NOTA 7 do UserRegistrationFlowTest).
     */
    private function reloadUser(string $email): User
    {
        $user = static::getContainer()->get("doctrine")->getManager()
            ->getRepository(User::class)
            ->findByEmail($email);

        $this->assertInstanceOf(User::class, $user);

        return $user;
    }

    private function signedLink(User $user, string $locale = "pt_BR"): string
    {
        return static::getContainer()->get(VerifyEmailHelperInterface::class)->generateSignature(
            VerifyEmailEntryPoint::ROUTE_NAME,
            (string) $user->getId(),
            (string) $user->getEmail(),
            ["id" => $user->getId(), "_locale" => $locale],
        )->getSignedUrl();
    }

    /**
     * Troca o último caractere da assinatura.
     */
    private function tamper(string $link): string
    {
        parse_str((string) parse_url($link, PHP_URL_QUERY), $query);
        $signature = (string) $query["signature"];
        $query["signature"] = substr($signature, 0, -1) . (str_ends_with($signature, "A") ? "B" : "A");

        return strtok($link, "?") . "?" . http_build_query($query);
    }

    private function queryOf(string $link): string
    {
        return (string) parse_url($link, PHP_URL_QUERY);
    }
}
