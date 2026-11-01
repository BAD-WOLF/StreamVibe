<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Person\ExternalIds;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Person\ExternalIds\Model\GetPersonExternalIdsOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\Shared\Routing\RouteRequirement;
use App\Infrastructure\Http\Controller\Person\ExternalIds\GetPersonExternalIdsController;

/**
 * GET /{_locale}/api/person/{personId}/external/ids — ids da pessoa em outros
 * sites (IMDb, Wikidata, redes sociais).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o GetPersonExternalIdsController, e o corpo de sucesso é o
 * GetPersonExternalIdsOutput.
 *
 * NOTA (out/2026): endpoint novo — antes os ids externos eram só um
 * placeholder sempre null no GetPersonDetailsUseCase. Rota com barra
 * (external/ids), como movie/credits e tv/credits.
 * - personId com RouteRequirement::INTEGER (até 18 dígitos): com '\d+', um
 *   número que não cabe em int virava TypeError (500) no controller.
 * - formats json e errors: [] como nas EntryPoints de filme (o controller
 *   responde application/json, e os erros reais estão em `responses`).
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/person/{personId}/external/ids',
                requirements: [
                    'personId' => RouteRequirement::INTEGER,
                ],
                status: 200,
                controller: GetPersonExternalIdsController::class,
                openapi: new Operation(
                    operationId: 'GetPersonExternalIds',
                    tags: ['People'],
                    responses: [
                        200 => new Response(
                            description: 'OK — external IDs of the person',
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
                    summary: 'Get person external IDs',
                    description: 'Retrieves the IDs of a person on other sites (IMDb, Wikidata, Facebook, Instagram, TikTok, X/Twitter, YouTube). Missing IDs come as null.',
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
                output: GetPersonExternalIdsOutput::class,
                errors: [],
                name: 'get_person_external_ids',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class GetPersonExternalIdsEntryPoint {
}
