<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Image\Original;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Application\Image\Shared\ImageFormat;
use App\Application\Image\Shared\ImageQuality;
use App\Infrastructure\ApiPlatform\Image\Content\Model\GetImageOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\Shared\Routing\RouteRequirement;
use App\Infrastructure\Http\Controller\Image\Original\GetOriginalImageController;

/**
 * GET /{_locale}/api/image/{endpoint} — uma imagem do TMDB no tamanho
 * original, como bytes (padrão), JSON base64 (format=base64) ou redirect
 * para o CDN (format=url).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o GetOriginalImageController; o corpo JSON (base64) é o mesmo
 * GetImageOutput da rota com tamanho (o OutputMapperRegistry liga um
 * Output por DTO, e as duas rotas devolvem o GetImageResponse).
 *
 * NOTA (out/2026):
 * - Saiu do antigo Image/ImageEntryPoint (uma EntryPoint por rota).
 * - {endpoint} não aceita mais barra (RouteRequirement::PATH_SEGMENT):
 *   antes, com '.+', esta rota também casava com "/image/w500/abc.jpg".
 * - Documentados ?format= (padrão binary), ?quality=, ?cache=, o redirect
 *   302 e os erros reais.
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/image/{endpoint}',
                requirements: [
                    'endpoint' => RouteRequirement::PATH_SEGMENT,
                ],
                status: 200,
                controller: GetOriginalImageController::class,
                openapi: new Operation(
                    operationId: 'GetOriginalImage',
                    tags: ['Images'],
                    responses: [
                        200 => new Response(
                            description: 'OK — with format=binary (default) the image itself (Content-Type image/jpeg, image/png, image/webp or image/gif); with format=base64 the JSON below',
                        ),
                        302 => new Response(
                            description: 'Found — with format=url, redirect (Location header) to the image on the TMDB CDN',
                        ),
                        400 => new Response(
                            description: 'Bad Request — invalid endpoint, format or quality',
                            content: new ErrorResponseSchema(
                                type: 'user_validation_error',
                                message: 'Multiple validation errors occurred',
                                errors: ['Format: Invalid response format. Allowed formats: "binary", "base64", "url"'],
                            ),
                        ),
                        401 => new Response(
                            description: 'Unauthorized — JWT missing, invalid or expired',
                            content: new UnauthorizedSchema(),
                        ),
                        404 => new Response(
                            description: 'Not Found — the image does not exist on TMDB',
                            content: new ErrorResponseSchema(
                                type: 'business_logic_error',
                                message: 'Image with identifier "/abc.jpg" not found',
                            ),
                        ),
                        502 => new Response(
                            description: 'Bad Gateway — TMDB answered with an error or with content that is not an image',
                            content: new ErrorResponseSchema(
                                type: 'external_service_error',
                                message: 'External service temporarily unavailable. Please try again later.',
                            ),
                        ),
                        503 => new Response(
                            description: 'Service Unavailable — TMDB unreachable or its rate limit was reached',
                            content: new ErrorResponseSchema(
                                type: 'external_service_error',
                                message: 'External service temporarily unavailable. Please try again later.',
                            ),
                        ),
                    ],
                    summary: 'Get a TMDB image in its original size',
                    description: 'Same as GET /image/{size}/{endpoint} with size "original", but the default format is binary.',
                    parameters: [
                        new Parameter(
                            name: '_locale',
                            in: 'path',
                            description: ParameterSchema::LOCALE_DESCRIPTION,
                            required: true,
                            schema: ParameterSchema::LOCALE,
                        ),
                        new Parameter(
                            name: 'endpoint',
                            in: 'path',
                            description: 'TMDB file name, as in poster_path/backdrop_path/file_path without the leading slash (e.g. "kqjL17yufvn9OVLyXYpvtyrFfak.jpg")',
                            required: true,
                            schema: [
                                'type' => 'string',
                                'pattern' => '^[a-zA-Z0-9_.-]+\.(jpg|jpeg|png|webp|gif)$',
                            ],
                        ),
                        new Parameter(
                            name: 'format',
                            in: 'query',
                            description: 'Response format',
                            required: false,
                            schema: [
                                'type' => 'string',
                                'enum' => ImageFormat::ALL,
                                'default' => ImageFormat::BINARY,
                            ],
                        ),
                        new Parameter(
                            name: 'quality',
                            in: 'query',
                            description: 'JPEG quality used to re-encode the image (only JPEG images; without it the image is returned as is)',
                            required: false,
                            schema: [
                                'type' => 'integer',
                                'minimum' => ImageQuality::MIN,
                                'maximum' => ImageQuality::MAX,
                            ],
                        ),
                        new Parameter(
                            name: 'cache',
                            in: 'query',
                            description: 'format=binary only: false sends Cache-Control: no-cache instead of public, max-age=86400',
                            required: false,
                            schema: [
                                'type' => 'boolean',
                                'default' => true,
                            ],
                        ),
                    ],
                ),
                output: GetImageOutput::class,
                errors: [],
                name: self::ROUTE_NAME,
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class GetOriginalImageEntryPoint {
    public const string ROUTE_NAME = 'get_image_original';
}
