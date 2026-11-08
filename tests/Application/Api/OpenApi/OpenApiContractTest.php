<?php

declare(strict_types=1);

namespace App\Tests\Application\Api\OpenApi;

use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\Role;
use App\Infrastructure\Persistence\Repository\UserRepository;
use App\Tests\Helper\ApiAuthenticationTrait;
use App\Tests\Helper\ApiRequestTrait;
use App\Tests\Helper\DatabaseTestTrait;
use App\Tests\Helper\UserTestBuilder;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * O Swagger documenta o JSON que a API devolve de verdade.
 *
 * Para cada rota, compara os campos documentados na resposta de sucesso
 * (o Output do API Platform: success/data/message, e os campos de `data`)
 * com os campos da resposta real da mesma rota. E, nas rotas com corpo,
 * os campos documentados do corpo com os que o controller lê.
 *
 * Só rotas que não dependem do TMDB, para o teste não depender de rede.
 *
 * NOTA (out/2026): criado com o auto-mapping funcionando. Antes o
 * OutputMapperRegistry nunca chegava aos controllers: o JSON real era o
 * toArray() de cada DTO e os Output models só alimentavam o Swagger — que
 * por isso documentava formatos diferentes do real (ex.: o pedido de troca
 * de senha documentado com envelope e `data`, e devolvido sem envelope,
 * com user_id e request_id no nível de cima). Este teste quebra se os dois
 * voltarem a se separar.
 */
final class OpenApiContractTest extends WebTestCase
{
    use DatabaseTestTrait;
    use ApiAuthenticationTrait;
    use ApiRequestTrait;

    private KernelBrowser $client;

    /**
     * @var array<string, mixed>
     */
    private array $openApi;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager = static::getContainer()->get("doctrine")->getManager();

        $this->setupTestDatabase();

        $this->client->request("GET", "/pt_BR/api/docs.jsonopenapi");
        $this->assertResponseIsSuccessful();
        $this->openApi = $this->responseData($this->client);
    }

    protected function tearDown(): void
    {
        $this->teardownTestDatabase();
        parent::tearDown();
    }

    public function testRegisterMatchesItsDocumentation(): void
    {
        $this->assertDocumentedBody("post", "/{_locale}/api/register", ["email", "password", "agreeTerms"]);

        $this->postJson($this->client, "/pt_BR/api/register", [
            "email" => "contract@example.com",
            "password" => "SecurePassword123!",
            "agreeTerms" => true,
        ]);

        $this->assertResponseMatchesDocumentation("post", "/{_locale}/api/register", 201);
    }

    public function testResendVerificationMatchesItsDocumentation(): void
    {
        $this->makeUser("contract-resend@example.com", verified: false);

        $this->assertDocumentedBody("post", "/{_locale}/api/resend-verification", ["email"]);

        $this->postJson($this->client, "/pt_BR/api/resend-verification", ["email" => "contract-resend@example.com"]);

        $this->assertResponseMatchesDocumentation("post", "/{_locale}/api/resend-verification", 200);
    }

    public function testVerificationStatusMatchesItsDocumentation(): void
    {
        $user = $this->makeUser("contract-status@example.com", verified: false);

        $this->client->request("GET", "/pt_BR/api/verify/email/status/" . $user->getId());

        $this->assertResponseMatchesDocumentation("get", "/{_locale}/api/verify/email/status/{userId}", 200);
    }

    public function testPasswordResetFlowMatchesItsDocumentation(): void
    {
        $user = $this->makeUser("contract-reset@example.com", verified: true);

        $this->assertDocumentedBody("post", "/{_locale}/api/reset-password", ["email"]);
        $this->postJson($this->client, "/pt_BR/api/reset-password", ["email" => "contract-reset@example.com"]);
        $this->assertResponseMatchesDocumentation("post", "/{_locale}/api/reset-password", 200);

        $statusToken = $this->resetTokenFor("contract-status-token@example.com");
        $this->client->request("GET", "/pt_BR/api/reset-password/status/" . $statusToken);
        $this->assertResponseMatchesDocumentation("get", "/{_locale}/api/reset-password/status/{token}", 200);

        $cancelToken = $this->resetTokenFor("contract-cancel@example.com");
        $this->assertDocumentedBody("post", "/{_locale}/api/reset-password/cancel", ["token"]);
        $this->postJson($this->client, "/pt_BR/api/reset-password/cancel", ["token" => $cancelToken]);
        $this->assertResponseMatchesDocumentation("post", "/{_locale}/api/reset-password/cancel", 200);

        $confirmToken = $this->resetTokenFor("contract-confirm@example.com");
        $this->assertDocumentedBody("post", "/{_locale}/api/reset-password/confirm", ["token", "password"]);
        $this->postJson($this->client, "/pt_BR/api/reset-password/confirm", [
            "token" => $confirmToken,
            "password" => "BrandNewSecurePassword456!",
        ]);
        $this->assertResponseMatchesDocumentation("post", "/{_locale}/api/reset-password/confirm", 200);

        $this->authenticateClient($this->client, $user);
        $this->client->request("GET", "/pt_BR/api/reset-password/requests/" . $user->getId());
        $this->assertResponseMatchesDocumentation("get", "/{_locale}/api/reset-password/requests/{userId}", 200);
    }

    /**
     * NOTA (nov/2026): o GET /me entrou com a sessão em cookie.
     */
    public function testCurrentUserMatchesItsDocumentation(): void
    {
        $this->authenticateClient($this->client, $this->makeUser("contract-me@example.com", verified: true));

        $this->client->request("GET", "/pt_BR/api/me");

        $this->assertResponseMatchesDocumentation("get", "/{_locale}/api/me", 200);
    }

    /**
     * NOTA (nov/2026): a administração de contas — todas as rotas, na ordem
     * em que um owner as usaria numa conta nova.
     */
    public function testAdminRoutesMatchTheirDocumentation(): void
    {
        $owner = $this->makeUser("contract-owner@example.com", verified: true);
        $owner->assignRole(Role::Owner);
        static::getContainer()->get("doctrine")->getManager()->flush();
        $target = $this->makeUser("contract-target@example.com", verified: false);
        $users = "/{_locale}/api/admin/users";
        $user = "{$users}/{userId}";
        $uri = "/pt_BR/api/admin/users/" . $target->getId();
        $this->authenticateClient($this->client, $owner);

        $this->client->request("GET", "/pt_BR/api/admin/users");
        $this->assertResponseMatchesDocumentation("get", $users, 200);

        $this->client->request("GET", $uri);
        $this->assertResponseMatchesDocumentation("get", $user, 200);

        $this->client->request("POST", "{$uri}/resend-verification");
        $this->assertResponseMatchesDocumentation("post", "{$user}/resend-verification", 200);

        $this->client->request("POST", "{$uri}/verify");
        $this->assertResponseMatchesDocumentation("post", "{$user}/verify", 200);

        $this->assertDocumentedBody("post", "{$user}/block", ["reason"]);
        $this->postJson($this->client, "{$uri}/block", ["reason" => "contract"]);
        $this->assertResponseMatchesDocumentation("post", "{$user}/block", 200);

        $this->client->request("POST", "{$uri}/unblock");
        $this->assertResponseMatchesDocumentation("post", "{$user}/unblock", 200);

        $this->assertDocumentedBody("post", "{$user}/role", ["role"]);
        $this->postJson($this->client, "{$uri}/role", ["role" => "ROLE_DEV"]);
        $this->assertResponseMatchesDocumentation("post", "{$user}/role", 200);

        $this->client->request("DELETE", "{$uri}/sessions");
        $this->assertResponseMatchesDocumentation("delete", "{$user}/sessions", 200);

        $this->client->request("GET", "/pt_BR/api/admin/audit-log");
        $this->assertResponseMatchesDocumentation("get", "/{_locale}/api/admin/audit-log", 200);

        $this->client->request("DELETE", $uri);
        $this->assertResponseMatchesDocumentation("delete", $user, 200);
    }

    public function testImageRoutesWithoutTmdbMatchTheirDocumentation(): void
    {
        $this->authenticateClient($this->client, $this->makeUser("contract-images@example.com", verified: true));

        $this->client->request("GET", "/pt_BR/api/images/sizes");
        $this->assertResponseMatchesDocumentation("get", "/{_locale}/api/images/sizes", 200);

        $this->client->request("GET", "/pt_BR/api/images/info/abc.jpg?size=w500");
        $this->assertResponseMatchesDocumentation("get", "/{_locale}/api/images/info/{endpoint}", 200);
    }

    /**
     * Toda operação da API documenta o {_locale} do prefixo.
     */
    public function testEveryOperationDocumentsTheLocale(): void
    {
        foreach ($this->openApi["paths"] as $path => $pathItem) {
            if (!str_starts_with($path, "/{_locale}/api/")) {
                continue;
            }

            foreach ($pathItem as $method => $operation) {
                if (!is_array($operation) || !isset($operation["responses"])) {
                    continue;
                }

                $names = array_column($operation["parameters"] ?? [], "name");
                $this->assertContains("_locale", $names, "{$method} {$path} does not document _locale");
            }
        }
    }

    /**
     * Toda lista documentada diz o que tem dentro: um schema ($ref) ou um
     * único tipo escalar.
     *
     * NOTA (out/2026): pega o caso das listas trailers/teasers/clips do
     * GET /movies/{movieId}/videos, que saíam documentadas como listas de
     * string (o PropertyInfo não via o tipo dos itens) enquanto o JSON real
     * trazia objetos MovieVideo. As rotas de filmes dependem do TMDB e por
     * isso não entram na comparação com a resposta real deste teste.
     */
    public function testEveryDocumentedListSaysWhatItContains(): void
    {
        foreach ($this->openApi["components"]["schemas"] as $schemaName => $schema) {
            foreach ($schema["properties"] ?? [] as $property => $definition) {
                if (!in_array("array", (array) ($definition["type"] ?? []), true)) {
                    continue;
                }

                $items = $definition["items"] ?? [];
                $scalarItems = is_string($items["type"] ?? null)
                    && in_array($items["type"], ["string", "integer", "number", "boolean"], true);

                $this->assertTrue(
                    isset($items['$ref']) || $scalarItems,
                    sprintf(
                        "%s.%s: the documentation does not say what the list contains (%s)",
                        $schemaName,
                        $property,
                        json_encode($items),
                    ),
                );
            }
        }
    }

    /**
     * Compara os campos da última resposta com os documentados.
     */
    private function assertResponseMatchesDocumentation(string $method, string $path, int $status): void
    {
        $this->assertResponseStatusCodeSame($status);
        $actual = $this->responseData($this->client);

        $schema = $this->openApi["paths"][$path][$method]["responses"][$status]["content"]["application/json"]["schema"] ?? null;
        $this->assertIsArray($schema, "{$method} {$path} documents no application/json body for {$status}");

        $documented = $this->properties($schema);
        $this->assertEqualsCanonicalizing(
            array_keys($documented),
            array_keys($actual),
            "{$method} {$path}: documented fields differ from the real response",
        );

        $this->assertArrayHasKey("data", $documented, "{$method} {$path}: no `data` documented");
        $this->assertEqualsCanonicalizing(
            array_keys($this->properties($documented["data"])),
            array_keys($actual["data"]),
            "{$method} {$path}: documented `data` fields differ from the real response",
        );
    }

    /**
     * @param list<string> $expectedFields campos que o controller lê do corpo
     */
    private function assertDocumentedBody(string $method, string $path, array $expectedFields): void
    {
        $schema = $this->openApi["paths"][$path][$method]["requestBody"]["content"]["application/json"]["schema"] ?? null;
        $this->assertIsArray($schema, "{$method} {$path} documents no application/json request body");

        $this->assertEqualsCanonicalizing(
            $expectedFields,
            array_keys($this->properties($schema)),
            "{$method} {$path}: documented request body differs from what the controller reads",
        );
    }

    /**
     * Propriedades de um schema, resolvendo $ref, allOf e anyOf/oneOf
     * (o API Platform usa anyOf para "objeto ou null").
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    private function properties(array $schema): array
    {
        if (isset($schema['$ref'])) {
            $name = substr((string) $schema['$ref'], strlen("#/components/schemas/"));
            $this->assertArrayHasKey($name, $this->openApi["components"]["schemas"], "Unknown schema {$name}");

            return $this->properties($this->openApi["components"]["schemas"][$name]);
        }

        $properties = $schema["properties"] ?? [];

        foreach ($schema["allOf"] ?? [] as $part) {
            $properties += $this->properties($part);
        }

        foreach (array_merge($schema["anyOf"] ?? [], $schema["oneOf"] ?? []) as $option) {
            if (($option["type"] ?? null) !== "null") {
                return $properties + $this->properties($option);
            }
        }

        return $properties;
    }

    /**
     * Salva pelo container atual: o KernelBrowser reinicia o kernel a
     * cada requisição, e o token de troca de senha precisa ser gerado no
     * mesmo EntityManager que conhece o usuário (ver NOTA 7 do
     * UserRegistrationFlowTest).
     */
    private function makeUser(string $email, bool $verified): User
    {
        $user = UserTestBuilder::create()
            ->withEmail($email)
            ->withPassword("hashed_password_123")
            ->verified($verified)
            ->build();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get("doctrine")->getManager()->getRepository(User::class);
        $userRepository->save($user);

        return $user;
    }

    private function resetTokenFor(string $email): string
    {
        $user = $this->makeUser($email, verified: true);

        return static::getContainer()->get(ResetPasswordHelperInterface::class)
            ->generateResetToken($user)
            ->getToken();
    }
}
