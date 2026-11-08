<?php

declare(strict_types=1);

namespace App\Tests\Application\Api\Security;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * CORS com credenciais (config/packages/nelmio_cors.yaml): o front, em
 * outra origem, chama o login, a renovação e o logout com credentials:
 * 'include', e o navegador só aceita o cookie e entrega a resposta se a
 * API devolver a própria Origin (nunca "*") e Allow-Credentials: true.
 *
 * Nos testes CORS_ALLOW_ORIGIN é "*" (phpunit.dist.xml), como no .env.dev.
 *
 * NOTA (nov/2026): criado com o refresh token em cookie.
 */
final class CorsApiTest extends WebTestCase
{
    private const string FRONT_ORIGIN = 'http://172.24.176.65:5173';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testThePreflightOfTheRefreshAllowsCredentialsForTheOrigin(): void
    {
        $this->client->request('OPTIONS', '/pt_BR/api/token/refresh', server: [
            'HTTP_ORIGIN' => self::FRONT_ORIGIN,
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Access-Control-Allow-Origin', self::FRONT_ORIGIN);
        $this->assertResponseHeaderSame('Access-Control-Allow-Credentials', 'true');
        $this->assertStringContainsString('POST', (string) $this->client->getResponse()->headers->get('Access-Control-Allow-Methods'));
    }

    /**
     * A resposta de verdade também ecoa a Origin, e deixa o front ler o
     * Retry-After (o tempo de espera depois de um 429).
     */
    public function testAResponseEchoesTheOriginAndExposesRetryAfter(): void
    {
        $this->client->request('POST', '/pt_BR/api/logout', server: ['HTTP_ORIGIN' => self::FRONT_ORIGIN]);

        $this->assertResponseHeaderSame('Access-Control-Allow-Origin', self::FRONT_ORIGIN);
        $this->assertResponseHeaderSame('Access-Control-Allow-Credentials', 'true');
        $this->assertStringContainsString(
            'retry-after',
            (string) $this->client->getResponse()->headers->get('Access-Control-Expose-Headers'),
        );
    }
}
