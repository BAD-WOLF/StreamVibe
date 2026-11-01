<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Image\Content;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Application\Image\Shared\ImageFormat;
use App\Application\Image\Shared\ImageQuality;
use App\Application\Image\Shared\ImageSize;
use App\Infrastructure\ApiPlatform\Image\Content\Model\GetImageOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\Shared\Routing\RouteRequirement;
use App\Infrastructure\Http\Controller\Image\Content\GetImageController;

/**
 * GET /{_locale}/api/image/{size}/{endpoint} — uma imagem do TMDB no
 * tamanho pedido, como JSON base64 (padrão), bytes (format=binary) ou
 * redirect para o CDN (format=url).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o GetImageController; o corpo JSON (base64) é o GetImageOutput.
 *
 * NOTA (out/2026):
 * - Saiu do antigo Image/ImageEntryPoint, que agrupava as quatro rotas de
 *   imagem: agora é uma EntryPoint por rota, espelhando
 *   Application/Image/{DTO,UseCase}/Content e Controller/Image/Content.
 * - {size} aceita todos os tamanhos do catálogo ImageSize (antes faltavam
 *   w45 e w300, que GET /images/sizes anunciava) e {endpoint} não aceita
 *   mais barra (RouteRequirement::PATH_SEGMENT), o que tira a ambiguidade
 *   com /image/{endpoint}.
 * - ROUTE_NAME existe para o GetImageSizesController gerar os exemplos de
 *   uso com o router.
 * - Documentados ?format=, ?quality= e ?cache=, o redirect 302 e os erros
 *   reais (formato do ExceptionListener). formats json e errors: [] como
 *   nas outras EntryPoints.
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/image/{size}/{endpoint}',
                requirements: [
                    'size' => ImageSize::ROUTE_REQUIREMENT,
                    'endpoint' => RouteRequirement::PATH_SEGMENT,
                ],
                status: 200,
                controller: GetImageController::class,
                openapi: new Operation(
                    operationId: 'GetImage',
                    tags: ['Images'],
                    responses: [
                        200 => new Response(
                            description: 'OK — with format=base64 (default) the JSON below; with format=binary the image itself (Content-Type image/jpeg, image/png, image/webp or image/gif)',
                        ),
                        302 => new Response(
                            description: 'Found — with format=url, redirect (Location header) to the image on the TMDB CDN',
                        ),
                        400 => new Response(
                            description: 'Bad Request — invalid endpoint, format or quality',
                            content: new ErrorResponseSchema(
                                type: 'user_validation_error',
                                message: 'Multiple validation errors occurred',
                                errors: ['Quality: Quality must be between 1 and 100'],
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
                    summary: 'Get a TMDB image in a given size',
                    description: 'Fetches an image from the TMDB CDN. format=base64 (default) returns JSON with a data URI, format=binary returns the image bytes and format=url redirects to the CDN. quality (1-100) re-encodes JPEG images.',
                    parameters: [
                        new Parameter(
                            name: '_locale',
                            in: 'path',
                            description: ParameterSchema::LOCALE_DESCRIPTION,
                            required: true,
                            schema: ParameterSchema::LOCALE,
                        ),
                        new Parameter(
                            name: 'size',
                            in: 'path',
                            description: 'TMDB image size (see GET /images/sizes for the sizes of each image type)',
                            required: true,
                            schema: [
                                'type' => 'string',
                                'enum' => ImageSize::ALL,
                            ],
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
                                'default' => ImageFormat::BASE64,
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
final class GetImageEntryPoint {
    public const string ROUTE_NAME = 'get_image_with_size';
}
