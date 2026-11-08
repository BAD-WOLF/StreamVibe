<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Headers de segurança em toda resposta da aplicação:
 * - X-Content-Type-Options: nosniff — o navegador não "adivinha" outro
 *   tipo para o corpo (um JSON nunca é lido como HTML ou script);
 * - X-Frame-Options: DENY — nenhuma página da API (o Swagger, os erros)
 *   abre dentro de um iframe de outro site (clickjacking);
 * - Referrer-Policy: strict-origin-when-cross-origin — um link aberto a
 *   partir da API leva para outro site só a origem dela, nunca o caminho
 *   e a query (onde podem estar tokens, como nos links de email);
 * - Content-Security-Policy "default-src 'none'; frame-ancestors 'none'",
 *   só nas respostas JSON: se um JSON for aberto como documento, nada
 *   dele roda nem carrega. As páginas HTML (o Swagger UI) ficam sem, porque
 *   dependem de scripts e estilos próprios;
 * - Strict-Transport-Security, só em requisição HTTPS (atrás de um proxy,
 *   com ele em trusted_proxies): o navegador passa a usar só HTTPS para a
 *   API por um ano.
 *
 * Um header que a resposta já tem não é trocado.
 *
 * NOTA (nov/2026): criado na revisão de segurança. Só o nginx de produção
 * (docker/nginx/default.conf) mandava headers assim; o servidor de dev e
 * qualquer outra forma de subir a API não tinham nenhum. Os três primeiros
 * têm aqui os mesmos valores do nginx, então em produção, onde os dois
 * mandam, o resultado não muda.
 */
final readonly class SecurityHeadersListener
{
    private const string JSON_CONTENT_SECURITY_POLICY = "default-src 'none'; frame-ancestors 'none'";

    private const string STRICT_TRANSPORT_SECURITY = 'max-age=31536000; includeSubDomains';

    /**
     * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
     *
     * @return void
     */
    #[AsEventListener(event: KernelEvents::RESPONSE)]
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $headers = $event->getResponse()->headers;

        $this->setIfMissing($headers, 'X-Content-Type-Options', 'nosniff');
        $this->setIfMissing($headers, 'X-Frame-Options', 'DENY');
        $this->setIfMissing($headers, 'Referrer-Policy', 'strict-origin-when-cross-origin');

        if (str_contains((string) $headers->get('Content-Type', ''), 'json')) {
            $this->setIfMissing($headers, 'Content-Security-Policy', self::JSON_CONTENT_SECURITY_POLICY);
        }

        if ($event->getRequest()->isSecure()) {
            $this->setIfMissing($headers, 'Strict-Transport-Security', self::STRICT_TRANSPORT_SECURITY);
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\ResponseHeaderBag $headers
     * @param string                                              $name
     * @param string                                              $value
     *
     * @return void
     */
    private function setIfMissing(ResponseHeaderBag $headers, string $name, string $value): void
    {
        if (!$headers->has($name)) {
            $headers->set($name, $value);
        }
    }
}
