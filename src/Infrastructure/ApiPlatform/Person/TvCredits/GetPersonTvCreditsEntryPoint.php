<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Person\TvCredits;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Person\TvCredits\Model\GetPersonTvCreditsOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\Shared\Routing\RouteRequirement;
use App\Infrastructure\Http\Controller\Person\TvCredits\GetPersonTvCreditsController;

/**
 * GET /{_locale}/api/person/{personId}/tv/credits — séries em que a pessoa
 * atuou (cast) e em que trabalhou na equipe técnica (crew).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o GetPersonTvCreditsController, e o corpo de sucesso é o
 * GetPersonTvCreditsOutput.
 *
 * NOTA (out/2026): endpoint novo — antes os créditos de TV eram só um
 * placeholder sempre null no GetPersonDetailsUseCase. Rota com barra
 * (tv/credits), como movie/credits. Mesma documentação do
 * GetPersonMovieCreditsEntryPoint.
 * - personId com RouteRequirement::INTEGER (até 18 dígitos): com '\d+', um
 *   número que não cabe em int virava TypeError (500) no controller.
 * - formats json e errors: [] como nas EntryPoints de filme (o controller
 *   responde application/json, e os erros reais estão em `responses`).
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/person/{personId}/tv/credits',
                requirements: [
                    'personId' => RouteRequirement::INTEGER,
                ],
                status: 200,
                controller: GetPersonTvCreditsController::class,
                openapi: new Operation(
                    operationId: 'GetPersonTvCredits',
                    tags: ['People'],
                    responses: [
                        200 => new Response(
                            description: 'OK — TV credits of the person',
                        ),
                        400 => new Response(
                            description: 'Bad Request — personId out of range or malformed language',
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
                    summary: 'Get person TV credits',
                    description: 'Retrieves the TV shows a person acted in (cast) and worked on (crew).',
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
                        new Parameter(
                            name: 'language',
                            in: 'query',
                            description: ParameterSchema::LANGUAGE_DESCRIPTION,
                            required: false,
                            schema: ParameterSchema::LANGUAGE,
                        ),
                    ],
                ),
                output: GetPersonTvCreditsOutput::class,
                errors: [],
                name: 'get_person_tv_credits',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class GetPersonTvCreditsEntryPoint {
}
