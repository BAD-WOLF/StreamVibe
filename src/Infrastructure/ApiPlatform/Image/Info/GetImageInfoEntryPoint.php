<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Image\Info;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Application\Image\Shared\ImageSize;
use App\Infrastructure\ApiPlatform\Image\Info\Model\GetImageInfoOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\Shared\Routing\RouteRequirement;
use App\Infrastructure\Http\Controller\Image\Info\GetImageInfoController;

/**
 * GET /{_locale}/api/images/info/{endpoint}?size= — URL no CDN e extensão
 * de uma imagem do TMDB, sem baixá-la.
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o GetImageInfoController, e o corpo de sucesso é o
 * GetImageInfoOutput.
 *
 * NOTA (out/2026):
 * - Saiu do antigo Image/ImageEntryPoint (uma EntryPoint por rota). O
 *   output declarado era o ImageOutput (das imagens base64), que não tinha
 *   nada a ver com a resposta; agora é o GetImageInfoOutput.
 * - ?size= documentado (antes só existia no controller) e {endpoint} sem
 *   barra (RouteRequirement::PATH_SEGMENT).
 * - ROUTE_NAME existe para o GetImageSizesController gerar o exemplo de
 *   uso com o router.
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/images/info/{endpoint}',
                requirements: [
                    'endpoint' => RouteRequirement::PATH_SEGMENT,
                ],
                status: 200,
                controller: GetImageInfoController::class,
                openapi: new Operation(
                    operationId: 'GetImageInfo',
                    tags: ['Images'],
                    responses: [
                        200 => new Response(
                            description: 'OK — URL and file extension of the image (TMDB is not queried)',
                        ),
                        400 => new Response(
                            description: 'Bad Request — invalid endpoint or size',
                            content: new ErrorResponseSchema(
                                type: 'user_validation_error',
                                message: 'Multiple validation errors occurred',
                                errors: ['Size: Invalid image size. Allowed sizes: "w45", "w92", "w154", "w185", "w300", "w342", "w500", "w780", "w1280", "h632", "original"'],
                            ),
                        ),
                        401 => new Response(
                            description: 'Unauthorized — JWT missing, invalid or expired',
                            content: new UnauthorizedSchema(),
                        ),
                    ],
                    summary: 'Get image information',
                    description: 'Returns the TMDB CDN URL and the file extension of an image for the given size, without downloading it.',
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
                            name: 'size',
                            in: 'query',
                            description: 'TMDB image size',
                            required: false,
                            schema: [
                                'type' => 'string',
                                'enum' => ImageSize::ALL,
                                'default' => ImageSize::ORIGINAL,
                            ],
                        ),
                    ],
                ),
                output: GetImageInfoOutput::class,
                errors: [],
                name: self::ROUTE_NAME,
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class GetImageInfoEntryPoint {
    public const string ROUTE_NAME = 'get_image_info';
}
