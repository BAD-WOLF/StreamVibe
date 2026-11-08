<?php

declare(strict_types=1);

namespace App\Tests\Application\Api\Security;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Os headers de segurança (SecurityHeadersListener) nas respostas reais:
 * de sucesso, de erro do firewall e de erro da aplicação.
 *
 * Nenhum caso aqui chega ao TMDB nem ao banco.
 *
 * NOTA (nov/2026): criado com os headers de segurança.
 */
final class SecurityHeadersApiTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testASuccessfulJsonResponseHasTheHeaders(): void
    {
        $this->client->request('GET', '/health');

        $this->assertResponseIsSuccessful();
        $this->assertSecurityHeaders($this->client->getResponse());
    }

    /**
     * O 401 sai do firewall (LexikJWT), antes de qualquer controller.
     */
    public function testTheFirewall401HasTheHeaders(): void
    {
        $this->client->request('GET', '/pt_BR/api/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertSecurityHeaders($this->client->getResponse());
    }

    /**
     * O 404 sai do ExceptionListener da aplicação.
     */
    public function testAnApplicationErrorHasTheHeaders(): void
    {
        $this->client->request('GET', '/xyz/api/images/sizes');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertSecurityHeaders($this->client->getResponse());
    }

    public function testHstsOnlyOverHttps(): void
    {
        $this->client->request('GET', '/health');
        $this->assertResponseNotHasHeader('Strict-Transport-Security');

        $this->client->request('GET', 'https://localhost/health');
        $this->assertResponseHeaderSame('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    private function assertSecurityHeaders(Response $response): void
    {
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertSame("default-src 'none'; frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
    }
}
