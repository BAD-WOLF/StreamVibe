<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Person\MovieCredits;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Person\MovieCredits\Model\GetPersonMovieCreditsOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\Shared\Routing\RouteRequirement;
use App\Infrastructure\Http\Controller\Person\MovieCredits\GetPersonMovieCreditsController;

/**
 * GET /{_locale}/api/person/{personId}/movie/credits — filmes em que a pessoa
 * atuou (cast) e em que trabalhou na equipe técnica (crew).
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o GetPersonMovieCreditsController, e o corpo de sucesso é o
 * GetPersonMovieCreditsOutput.
 *
 * NOTA (out/2026):
 * - Era Person/Credits/PersonMovieCreditsEntryPoint; renomeado para
 *   espelhar o DTO/UseCase/Controller (GetPersonMovieCredits*). Rota
 *   mantida: /person/{personId}/movie/credits.
 * - Ganhou `_locale`, `language`, tag, summary/description e as respostas
 *   de erro reais (ErrorResponseSchema/UnauthorizedSchema).
 * - personId com RouteRequirement::INTEGER (até 18 dígitos): com '\d+', um
 *   número que não cabe em int virava TypeError (500) no controller.
 * - formats json e errors: [] como nas EntryPoints de filme (o controller
 *   responde application/json, e os erros reais estão em `responses`).
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/person/{personId}/movie/credits',
                requirements: [
                    'personId' => RouteRequirement::INTEGER,
                ],
                status: 200,
                controller: GetPersonMovieCreditsController::class,
                openapi: new Operation(
                    operationId: 'GetPersonMovieCredits',
                    tags: ['People'],
                    responses: [
                        200 => new Response(
                            description: 'OK — movie credits of the person',
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
                    summary: 'Get person movie credits',
                    description: 'Retrieves the movies a person acted in (cast) and worked on (crew).',
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
                output: GetPersonMovieCreditsOutput::class,
                errors: [],
                name: 'get_person_movie_credits',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class GetPersonMovieCreditsEntryPoint {
}
