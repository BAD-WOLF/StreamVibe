<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movie\Trending;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Movie\Trending\Model\GetTrendingMoviesOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\Http\Controller\Movie\Trending\GetTrendingMoviesController;

/**
 * GET /{_locale}/api/movies/trending/{timeWindow} — filmes em alta no TMDB
 * no dia ou na semana.
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o GetTrendingMoviesController, e o corpo de sucesso é o
 * GetTrendingMoviesOutput.
 *
 * NOTA (out/2026):
 * - Saiu do antigo Movies/Search/MoviesEntryPoint (uma EntryPoint por
 *   endpoint, espelhando Application/Movie/{DTO,UseCase}/Trending e
 *   Controller/Movie/Trending).
 * - O {timeWindow} da rota agora é o que vale (antes o controller lia
 *   ?time_window= e /trending/day devolvia a semana). Valor fora de
 *   day|week não casa com a rota (404).
 * - `page` e `language` documentados (antes a lista ignorava ambos no
 *   Swagger, e `language` era ignorado de fato).
 * - formats json e errors: [] pelo mesmo motivo do SearchMoviesEntryPoint
 *   (o controller responde application/json, e os erros reais estão em
 *   `responses`).
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/movies/trending/{timeWindow}',
                requirements: [
                    'timeWindow' => 'day|week',
                ],
                status: 200,
                controller: GetTrendingMoviesController::class,
                openapi: new Operation(
                    operationId: 'GetMovieTrending',
                    tags: ['Movies'],
                    responses: [
                        200 => new Response(
                            description: 'OK — page of trending movies',
                        ),
                        400 => new Response(
                            description: 'Bad Request — invalid page or language',
                            content: new ErrorResponseSchema(
                                type: 'user_validation_error',
                                message: 'Multiple validation errors occurred',
                                errors: ['Page: Page must be between 1 and 500'],
                            ),
                        ),
                        401 => new Response(
                            description: 'Unauthorized — JWT missing, invalid or expired',
                            content: new UnauthorizedSchema(),
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
                    summary: 'Get trending movies',
                    description: 'Lists the movies trending on TMDB in the given time window (day or week).',
                    parameters: [
                        new Parameter(
                            name: '_locale',
                            in: 'path',
                            description: ParameterSchema::LOCALE_DESCRIPTION,
                            required: true,
                            schema: ParameterSchema::LOCALE,
                        ),
                        new Parameter(
                            name: 'timeWindow',
                            in: 'path',
                            description: 'Time window of the trend',
                            required: true,
                            schema: [
                                'type' => 'string',
                                'enum' => ['day', 'week'],
                            ],
                        ),
                        new Parameter(
                            name: 'page',
                            in: 'query',
                            description: ParameterSchema::PAGE_DESCRIPTION,
                            required: false,
                            schema: ParameterSchema::PAGE,
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
                output: GetTrendingMoviesOutput::class,
                errors: [],
                name: 'get_trending_movies',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class GetTrendingMoviesEntryPoint {
}
