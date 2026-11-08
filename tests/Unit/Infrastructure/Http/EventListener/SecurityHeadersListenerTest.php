<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http\EventListener;

use App\Infrastructure\Http\EventListener\SecurityHeadersListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * SecurityHeadersListener: os headers em toda resposta, a CSP só no JSON,
 * o HSTS só em HTTPS e nada trocado quando a resposta já traz o header.
 *
 * NOTA (nov/2026): criado com os headers de segurança.
 */
final class SecurityHeadersListenerTest extends TestCase
{
    public function testJsonResponsesGetEveryHeaderAndTheStrictCsp(): void
    {
        $response = $this->dispatch(new JsonResponse(['success' => true]));

        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        self::assertSame('DENY', $response->headers->get('X-Frame-Options'));
        self::assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        self::assertSame("default-src 'none'; frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
        self::assertFalse($response->headers->has('Strict-Transport-Security'));
    }

    /**
     * application/problem+json (erros do API Platform) também é JSON.
     */
    public function testProblemJsonAlsoGetsTheCsp(): void
    {
        $response = $this->dispatch(new Response('{}', 400, ['Content-Type' => 'application/problem+json']));

        self::assertSame("default-src 'none'; frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
    }

    /**
     * Páginas HTML (o Swagger UI) dependem de scripts e estilos próprios.
     */
    public function testHtmlPagesGetNoCsp(): void
    {
        $response = $this->dispatch(new Response('<html></html>', 200, ['Content-Type' => 'text/html; charset=UTF-8']));

        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        self::assertSame('DENY', $response->headers->get('X-Frame-Options'));
        self::assertFalse($response->headers->has('Content-Security-Policy'));
    }

    public function testHttpsRequestsGetHsts(): void
    {
        $response = $this->dispatch(new JsonResponse([]), Request::create('https://api.example.com/en/api/me'));

        self::assertSame('max-age=31536000; includeSubDomains', $response->headers->get('Strict-Transport-Security'));
    }

    public function testAHeaderTheResponseAlreadyHasIsKept(): void
    {
        $response = $this->dispatch(new JsonResponse([], 200, [
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "default-src 'self'",
        ]));

        self::assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        self::assertSame("default-src 'self'", $response->headers->get('Content-Security-Policy'));
    }

    public function testSubRequestsAreLeftAlone(): void
    {
        $response = $this->dispatch(new JsonResponse([]), null, HttpKernelInterface::SUB_REQUEST);

        self::assertFalse($response->headers->has('X-Frame-Options'));
    }

    private function dispatch(
        Response $response,
        ?Request $request = null,
        int $type = HttpKernelInterface::MAIN_REQUEST,
    ): Response {
        $event = new ResponseEvent(
            $this->createStub(HttpKernelInterface::class),
            $request ?? Request::create('http://localhost/en/api/me'),
            $type,
            $response,
        );

        new SecurityHeadersListener()->onKernelResponse($event);

        return $event->getResponse();
    }
}
