<?php

declare(strict_types=1);

namespace App\Tests\Application\Api\Security;

use App\Tests\Helper\ApiRequestTrait;
use App\Tests\Helper\DatabaseTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Quem acessa o quê sem JWT (config/packages/security.yaml e os
 * requirements de {_locale} das rotas).
 *
 * Nenhum caso aqui chega ao TMDB: as rotas protegidas param no firewall
 * (401) e as de locale inválido no roteamento (404).
 *
 * NOTA (out/2026): criado com a correção do {_locale}. O prefixo
 * /{_locale}/api aceitava qualquer segmento, mas o firewall "api" e o
 * access_control só reconhecem "xx" e "xx_XX": com /xyz/api/..., /pt-BR/api/...
 * ou /en_us/api/... a requisição caía no firewall "main", nenhuma regra
 * casava, e filmes, pessoas e imagens respondiam sem token
 * (testUnknownLocaleIsANotFound() era 200/502 antes). Também cobre o
 * /test/mapping, que era público em produção.
 */
final class ApiAccessControlTest extends WebTestCase
{
    use DatabaseTestTrait;
    use ApiRequestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get("doctrine")->getManager();
        $this->setupTestDatabase();
    }

    protected function tearDown(): void
    {
        $this->teardownTestDatabase();
        parent::tearDown();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function protectedRouteProvider(): iterable
    {
        yield "movies (pt_BR)" => ["/pt_BR/api/movies/popular"];
        yield "movies (en)" => ["/en/api/movies/popular"];
        yield "movies (es)" => ["/es/api/movies/550"];
        yield "search" => ["/pt_BR/api/movies/search/batman/1"];
        yield "person" => ["/pt_BR/api/person/287/details"];
        yield "image sizes" => ["/pt_BR/api/images/sizes"];
        yield "image" => ["/pt_BR/api/image/w92/abc.jpg?format=url"];
        yield "reset requests of a user" => ["/pt_BR/api/reset-password/requests/1"];
        // NOTA (nov/2026): a conta de quem está logado.
        yield "current user" => ["/pt_BR/api/me"];
    }

    #[DataProvider("protectedRouteProvider")]
    public function testProtectedRouteRequiresAJwt(string $uri): void
    {
        $this->client->request("GET", $uri);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unknownLocaleProvider(): iterable
    {
        yield "three letters" => ["/xyz/api/images/sizes"];
        yield "BCP 47 with a dash" => ["/pt-BR/api/images/sizes"];
        yield "lowercase region" => ["/en_us/api/movies/popular"];
        yield "uppercase" => ["/PT/api/person/287/details"];
        yield "locale outside the list" => ["/fr/api/images/sizes"];
    }

    #[DataProvider("unknownLocaleProvider")]
    public function testUnknownLocaleIsANotFound(string $uri): void
    {
        $this->client->request("GET", $uri);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * Fluxos de conta: quem os usa não tem (ou não tem mais) JWT. A
     * resposta de cada um é o erro da própria rota, não o 401.
     *
     * @return iterable<string, array{string, string, array<string, mixed>|null, int}>
     */
    public static function publicRouteProvider(): iterable
    {
        yield "register" => ["POST", "/pt_BR/api/register", [], Response::HTTP_BAD_REQUEST];
        yield "resend verification" => ["POST", "/pt_BR/api/resend-verification", [], Response::HTTP_BAD_REQUEST];
        yield "verify email" => ["GET", "/pt_BR/api/verify/email", null, Response::HTTP_BAD_REQUEST];
        yield "verification status" => ["GET", "/pt_BR/api/verify/email/status/999999", null, Response::HTTP_NOT_FOUND];
        yield "validate link" => ["GET", "/pt_BR/api/verify/email/validate", null, Response::HTTP_BAD_REQUEST];
        yield "reset password" => ["POST", "/pt_BR/api/reset-password", [], Response::HTTP_BAD_REQUEST];
        yield "reset status" => ["GET", "/pt_BR/api/reset-password/status/" . str_repeat("a", 40), null, Response::HTTP_BAD_REQUEST];
        yield "reset cancel" => ["POST", "/pt_BR/api/reset-password/cancel", [], Response::HTTP_BAD_REQUEST];
        yield "reset confirm" => ["POST", "/pt_BR/api/reset-password/confirm", [], Response::HTTP_BAD_REQUEST];
    }

    /**
     * @param array<string, mixed>|null $body
     */
    #[DataProvider("publicRouteProvider")]
    public function testAccountRoutesArePublic(string $method, string $uri, ?array $body, int $expectedStatus): void
    {
        if ($method === "POST") {
            $this->postJson($this->client, $uri, $body ?? []);
        } else {
            $this->client->request($method, $uri);
        }

        $this->assertResponseStatusCodeSame($expectedStatus);
        $this->assertFalse($this->responseData($this->client)["success"]);
    }

    public function testDocsArePublic(): void
    {
        $this->client->request("GET", "/pt_BR/api/docs.jsonopenapi");

        $this->assertResponseIsSuccessful();
    }

    public function testHealthCheckIsPublicAndOutsideTheApi(): void
    {
        $this->client->request("GET", "/health");

        $this->assertResponseIsSuccessful();
        $this->assertSame(["status" => "ok"], $this->responseData($this->client));
    }

    /**
     * Rota de diagnóstico do auto-mapping: só existe em dev.
     *
     * NOTA (out/2026): pede JSON, como o cliente dessa rota (ela responde
     * JSON em dev). Sem o Accept, o 404 de um caminho fora de /api fica com
     * o ErrorListener do Symfony, que registra todo 404 como "error" — e a
     * linha "[error] Uncaught PHP Exception ... No route found for GET
     * /test/mapping" aparecia no meio da saída do PHPUnit, parecendo falha.
     * Com o Accept quem responde é o ExceptionListener da aplicação, que
     * registra 4xx como warning. O que o teste confere não muda: a rota não
     * existe fora de dev.
     */
    public function testMappingDiagnosticDoesNotExistOutsideDev(): void
    {
        $this->client->request("GET", "/test/mapping", server: ["HTTP_ACCEPT" => "application/json"]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
