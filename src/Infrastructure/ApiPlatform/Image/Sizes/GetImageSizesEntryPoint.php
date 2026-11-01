<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Image\Sizes;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Image\Sizes\Model\GetImageSizesOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\Http\Controller\Image\Sizes\GetImageSizesController;

/**
 * GET /{_locale}/api/images/sizes — tamanhos (por tipo de imagem),
 * formatos e limites de qualidade aceitos pelas rotas de imagem, com
 * exemplos de uso.
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o GetImageSizesController, e o corpo de sucesso é o
 * GetImageSizesOutput.
 *
 * NOTA (out/2026): saiu do antigo Image/ImageEntryPoint (uma EntryPoint
 * por rota). Ganhou tag, summary/description e o 401 (a rota exige JWT
 * como o resto da API — por isso o healthcheck de produção, que chamava
 * /api/images/sizes, nunca passava; ele agora usa /health).
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/images/sizes',
                status: 200,
                controller: GetImageSizesController::class,
                openapi: new Operation(
                    operationId: 'GetImageSizes',
                    tags: ['Images'],
                    responses: [
                        200 => new Response(
                            description: 'OK — supported sizes, formats, quality limits and usage examples',
                        ),
                        401 => new Response(
                            description: 'Unauthorized — JWT missing, invalid or expired',
                            content: new UnauthorizedSchema(),
                        ),
                    ],
                    summary: 'List supported image sizes and formats',
                    description: 'Lists the TMDB image sizes per image type, the response formats and the JPEG quality limits accepted by the image routes, with usage examples for the current locale.',
                    parameters: [
                        new Parameter(
                            name: '_locale',
                            in: 'path',
                            description: ParameterSchema::LOCALE_DESCRIPTION,
                            required: true,
                            schema: ParameterSchema::LOCALE,
                        ),
                    ],
                ),
                output: GetImageSizesOutput::class,
                errors: [],
                name: 'get_image_sizes',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class GetImageSizesEntryPoint {
}
