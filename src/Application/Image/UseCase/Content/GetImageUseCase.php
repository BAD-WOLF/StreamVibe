<?php

declare(strict_types = 1);

namespace App\Application\Image\UseCase\Content;

use App\Application\Image\DTO\Content\Request\GetImageRequest;
use App\Application\Image\DTO\Content\Response\GetImageResponse;
use App\Application\Image\Port\ImageProcessorInterface;
use App\Application\Image\Shared\ImageFormat;
use App\Application\Image\Shared\ImageQuality;
use App\Application\Shared\Gateway\MovieDatabaseGatewayInterface;
use App\Application\Shared\RequestValidator;
use App\Application\Shared\TmdbImageUrlBuilder;
use App\Domain\Exception\BusinessLogicException;
use App\Domain\Exception\ExternalServiceException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Busca uma imagem no CDN do TMDB e a devolve no formato pedido:
 * binary (bytes), base64 (data URI) ou url (só a URL, sem baixar).
 * Com `quality`, recomprime JPEG antes de devolver.
 *
 * Erros: 400 (validação), 404 (imagem inexistente no TMDB), 502 (o TMDB
 * devolveu algo que não é imagem, ou erro) e 503 (TMDB inacessível).
 *
 * NOTA (out/2026): reescrito.
 * - Bug corrigido: a validação montava os erros com chaves numéricas
 *   (array_combine(range(...))) e chamava withContext(), que fazia
 *   `clone` da exceção — duas formas de TypeError/Error, então todo
 *   pedido inválido (tamanho, formato, caminho) virava 500. Agora usa o
 *   RequestValidator, como os outros UseCases: 400 com o campo.
 * - Depende das portas MovieDatabaseGatewayInterface e
 *   ImageProcessorInterface, não mais do TmdbApiService nem de
 *   finfo/GD direto. O try/catch das exceções do HttpClient saiu (o
 *   adaptador traduz: 404 do TMDB = imagem inexistente), e com ele a
 *   detecção de "429" no texto da mensagem, que lançava um erro de limite
 *   da NOSSA API.
 * - `quality` só recomprime JPEG, e vale também para base64 (antes só
 *   binary — e recomprimia qualquer formato para JPEG, mantendo o
 *   Content-Type original).
 * - Conteúdo que não é imagem agora é 502 (veio errado do TMDB), não 422.
 * - Saíram executeBatch(), executeWithFallback(), validateEndpoint(),
 *   getSupportedSizes() e getSupportedFormats(): ninguém chamava, e os
 *   dois últimos repetiam (diferente) o que GET /images/sizes devolve.
 *   O generateImageMetadata() também saiu: o metadata era calculado
 *   (com getimagesizefromstring) e nunca chegava ao JSON do base64.
 */
final readonly class GetImageUseCase {
    /**
     * Tipos que a API devolve (os formatos do CDN do TMDB). SVG fica de
     * fora: ver a constraint ImageEndpoint.
     */
    private const array SUPPORTED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    /**
     * @param \App\Application\Shared\Gateway\MovieDatabaseGatewayInterface $movieDatabaseGateway
     * @param \App\Application\Image\Port\ImageProcessorInterface           $imageProcessor
     * @param \App\Application\Shared\RequestValidator                      $requestValidator
     * @param \App\Application\Shared\TmdbImageUrlBuilder                   $imageUrlBuilder
     * @param \Psr\Log\LoggerInterface                                      $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface            $translator
     */
    public function __construct(
        private MovieDatabaseGatewayInterface $movieDatabaseGateway,
        private ImageProcessorInterface $imageProcessor,
        private RequestValidator $requestValidator,
        private TmdbImageUrlBuilder $imageUrlBuilder,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Image\DTO\Content\Request\GetImageRequest $request
     *
     * @return \App\Application\Image\DTO\Content\Response\GetImageResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\BusinessLogicException
     * @throws \App\Domain\Exception\ExternalServiceException
     */
    public function execute(GetImageRequest $request): GetImageResponse {
        $this->requestValidator->validate(
            $request,
            'image_request',
            ['endpoint' => $request->endpoint, 'size' => $request->size],
        );

        $url = (string) $this->imageUrlBuilder->build($request->endpoint, $request->size);

        if ($request->format === ImageFormat::URL) {
            return GetImageResponse::url(
                endpoint: $request->endpoint,
                size: $request->size,
                url: $url,
                message: $this->translator->trans('Image URL generated successfully'),
            );
        }

        $content = $this->movieDatabaseGateway->getImage(
            $request->size,
            $request->endpoint,
        );

        if ($content === '') {
            $this->logger->warning('Empty image content received from TMDB', [
                'endpoint' => $request->endpoint,
                'size' => $request->size,
            ]);

            throw BusinessLogicException::notFound('Image', $request->endpoint);
        }

        $mimeType = $this->imageProcessor->detectMimeType($content);

        if ($mimeType === null || !in_array($mimeType, self::SUPPORTED_MIME_TYPES, true)) {
            $this->logger->error('TMDB returned content that is not a supported image', [
                'endpoint' => $request->endpoint,
                'size' => $request->size,
                'detected_mime_type' => $mimeType,
                'content_length' => strlen($content),
            ]);

            throw ExternalServiceException::invalidResponse(
                'TMDB',
                'Content is not a supported image',
                [
                    'endpoint' => $request->endpoint,
                    'detected_mime_type' => $mimeType,
                ],
            );
        }

        if (
            $request->quality !== null
            && $request->quality < ImageQuality::MAX
            && $mimeType === 'image/jpeg'
        ) {
            $content = $this->imageProcessor->recompressJpeg($content, $request->quality);
        }

        $this->logger->info('Image retrieved successfully', [
            'endpoint' => $request->endpoint,
            'size' => $request->size,
            'format' => $request->format,
            'mime_type' => $mimeType,
            'content_length' => strlen($content),
        ]);

        $message = $this->translator->trans('Image retrieved successfully');

        return $request->format === ImageFormat::BINARY
            ? GetImageResponse::binary(
                endpoint: $request->endpoint,
                size: $request->size,
                mimeType: $mimeType,
                content: $content,
                url: $url,
                message: $message,
            )
            : GetImageResponse::base64(
                endpoint: $request->endpoint,
                size: $request->size,
                mimeType: $mimeType,
                content: $content,
                url: $url,
                message: $message,
            );
    }
}
