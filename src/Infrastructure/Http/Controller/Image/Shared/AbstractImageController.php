<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Image\Shared;

use App\Application\Image\DTO\Content\Response\GetImageResponse;
use App\Application\Image\Shared\ImageFormat;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base das duas rotas que devolvem a imagem (GetImageController e
 * GetOriginalImageController): transforma o GetImageResponse na resposta
 * HTTP do formato pedido.
 *
 * - binary: os bytes, com Content-Type da imagem, Content-Length e
 *   Cache-Control (?cache=false desliga o cache);
 * - url: redirect 302 para o CDN do TMDB;
 * - base64: JSON pelo auto-mapping (GetImageOutput).
 *
 * NOTA (out/2026): ImageController e ImageOriginalController tinham cada
 * um uma cópia de handleImageResponse(), shouldReturnJsonError() e
 * validateEndpoint(), com method_exists() para adivinhar o tipo da
 * resposta e três fallbacks de JSON (toJsonResponse(), toArray() e o
 * objeto cru). Ficou uma implementação só, tipada. Os erros saem do
 * ExceptionListener (antes: 500 com a mensagem crua da exceção, ou 404
 * text/plain para qualquer erro no formato binary).
 */
abstract class AbstractImageController extends AbstractController
{
    use OutputMappingTrait;

    private const string CACHE_CONTROL_ENABLED = 'public, max-age=86400';

    private const string CACHE_CONTROL_DISABLED = 'no-cache';

    /**
     * @param \App\Application\Image\DTO\Content\Response\GetImageResponse $response
     * @param \Symfony\Component\HttpFoundation\Request                    $request  para ler ?cache=
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function respondWithImage(GetImageResponse $response, Request $request): Response
    {
        return match ($response->format) {
            ImageFormat::URL => $this->redirect($response->url, Response::HTTP_FOUND),
            ImageFormat::BINARY => new Response(
                (string) $response->content,
                Response::HTTP_OK,
                [
                    'Content-Type' => (string) $response->mimeType,
                    'Content-Length' => (string) $response->contentLength,
                    'Cache-Control' => $request->query->getBoolean('cache', true)
                        ? self::CACHE_CONTROL_ENABLED
                        : self::CACHE_CONTROL_DISABLED,
                    // O tipo vem do conteúdo (ImageProcessorInterface), não
                    // da extensão; nosniff impede o navegador de reinterpretar.
                    'X-Content-Type-Options' => 'nosniff',
                ],
            ),
            default => $this->mapToJsonResponse(dto: $response, status: Response::HTTP_OK),
        };
    }

    /**
     * ?quality= é opcional; valor não numérico responde 400
     * (InputBag::getInt() lança BadRequestException).
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return int|null
     */
    protected function qualityFrom(Request $request): ?int
    {
        return $request->query->has('quality')
            ? $request->query->getInt('quality')
            : null;
    }
}
