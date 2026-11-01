<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Person\Images;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Person\Images\Model\GetPersonImagesOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\Shared\Routing\RouteRequirement;
use App\Infrastructure\Http\Controller\Person\Images\GetPersonImagesController;

/**
 * GET /{_locale}/api/person/{personId}/images — fotos de perfil de uma pessoa.
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o GetPersonImagesController, e o corpo de sucesso é o
 * GetPersonImagesOutput.
 *
 * NOTA (out/2026): endpoint novo — antes as imagens de pessoa eram só um
 * placeholder sempre null no GetPersonDetailsUseCase. Sem `language`: o
 * endpoint de imagens de pessoa do TMDB não tem esse parâmetro.
 * - personId com RouteRequirement::INTEGER (até 18 dígitos): com '\d+', um
 *   número que não cabe em int virava TypeError (500) no controller.
 * - formats json e errors: [] como nas EntryPoints de filme (o controller
 *   responde application/json, e os erros reais estão em `responses`).
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/person/{personId}/images',
                requirements: [
                    'personId' => RouteRequirement::INTEGER,
                ],
                status: 200,
                controller: GetPersonImagesController::class,
                openapi: new Operation(
                    operationId: 'GetPersonImages',
                    tags: ['People'],
                    responses: [
                        200 => new Response(
                            description: 'OK — profile pictures of the person',
                        ),
                        400 => new Response(
                            description: 'Bad Request — personId out of range',
                            content: new ErrorResponseSchema(
                                type: 'user_validation_error',
                                message: 'Multiple validation errors occurred',
                                errors: ['Person_id: Person ID must be between 1 and 999999999'],
                            ),
                        ),
                        401 => new Response(
                            description: 'Unauthorized — JWT missing, invalid or expired',
                            content: new UnauthorizedSchema(),
                        ),
                        404 => new Response(
                            description: 'Not Found — the person does not exist on TMDB',
                            content: new ErrorResponseSchema(
                                type: 'business_logic_error',
                                message: 'Person with identifier "999999999" not found',
                            ),
                        ),
                        502 => new Response(
                            description: 'Bad Gateway — TMDB answered with an error or with an invalid body',
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
                    summary: 'Get person images',
                    description: 'Retrieves the profile pictures of a person, each with ready-to-use URLs (original, w185 and h632).',
                    parameters: [
                        new Parameter(
                            name: '_locale',
                            in: 'path',
                            description: ParameterSchema::LOCALE_DESCRIPTION,
                            required: true,
                            schema: ParameterSchema::LOCALE,
                        ),
                        new Parameter(
                            name: 'personId',
                            in: 'path',
                            description: 'TMDB ID of the person',
                            required: true,
                            schema: ParameterSchema::TMDB_ID,
                        ),
                    ],
                ),
                output: GetPersonImagesOutput::class,
                errors: [],
                name: 'get_person_images',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class GetPersonImagesEntryPoint {
}
