<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movie\Search;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Movie\Search\Model\SearchMoviesOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\Shared\Routing\RouteRequirement;
use App\Infrastructure\Http\Controller\Movie\Search\SearchMoviesController;

/**
 * GET /{_locale}/api/movies/search/{query}/{page} — busca de filmes por
 * título no TMDB.
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o SearchMoviesController, e o corpo de sucesso é o
 * SearchMoviesOutput (o mesmo objeto que o controller serializa).
 *
 * NOTA (out/2026):
 * - Saiu do antigo Movies/Search/MoviesEntryPoint, que agrupava as cinco
 *   listas de filmes: agora é uma EntryPoint por endpoint, espelhando
 *   Application/Movie/{DTO,UseCase}/Search e Controller/Movie/Search.
 * - Documentados os filtros que o controller já lia e o UseCase ignorava
 *   (include_adult, region, year, primary_release_year) e o novo
 *   `language`. `page` deixou de aparecer como opcional: parâmetro de
 *   path é sempre obrigatório.
 * - Requirement de `page` via RouteRequirement::INTEGER (até 18
 *   dígitos): com '\d+', uma página que não cabe em int chegava ao
 *   controller e virava TypeError (500).
 * - formats json: sem isso o Swagger documentava application/ld+json
 *   (formato padrão do API Platform), mas o controller responde
 *   application/json.
 * - errors: [] desliga as respostas de erro que o API Platform inventa
 *   sozinho (404 "Not found" em problem+json), que esta rota nunca
 *   devolve. Os erros reais — no formato do ExceptionListener — estão em
 *   `responses`.
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/movies/search/{query}/{page}',
                requirements: [
                    // '.*' (e não '.+') para a query vazia (//1) chegar ao
                    // controller e virar 400 "Required field 'query' is
                    // missing", em vez de 404 de rota.
                    'query' => '.*',
                    'page' => RouteRequirement::INTEGER,
                ],
                status: 200,
                controller: SearchMoviesController::class,
                openapi: new Operation(
                    operationId: 'GetMovieSearch',
                    tags: ['Movies'],
                    responses: [
                        200 => new Response(
                            description: 'OK — page of movies matching the query',
                        ),
                        400 => new Response(
                            description: 'Bad Request — empty query or invalid filter (page, language, region, year)',
                            content: new ErrorResponseSchema(
                                type: 'user_validation_error',
                                message: "Required field 'query' is missing",
                                errors: ["The field 'query' is required"],
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
                    summary: 'Search movies by title',
                    description: 'Searches TMDB movies by title. Optional filters: include_adult, language, region, year and primary_release_year.',
                    parameters: [
                        new Parameter(
                            name: '_locale',
                            in: 'path',
                            description: ParameterSchema::LOCALE_DESCRIPTION,
                            required: true,
                            schema: ParameterSchema::LOCALE,
                        ),
                        new Parameter(
                            name: 'query',
                            in: 'path',
                            description: 'Search query (1 to 255 characters)',
                            required: true,
                            schema: [
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 255,
                            ],
                        ),
                        new Parameter(
                            name: 'page',
                            in: 'path',
                            description: ParameterSchema::PAGE_DESCRIPTION,
                            required: true,
                            schema: ParameterSchema::PAGE,
                        ),
                        new Parameter(
                            name: 'include_adult',
                            in: 'query',
                            description: 'Include adult titles in the results',
                            required: false,
                            schema: [
                                'type' => 'boolean',
                                'default' => false,
                            ],
                        ),
                        new Parameter(
                            name: 'language',
                            in: 'query',
                            description: ParameterSchema::LANGUAGE_DESCRIPTION,
                            required: false,
                            schema: ParameterSchema::LANGUAGE,
                        ),
                        new Parameter(
                            name: 'region',
                            in: 'query',
                            description: 'ISO 3166-1 region used to filter the release dates (e.g. "BR")',
                            required: false,
                            schema: [
                                'type' => 'string',
                                'pattern' => '^[A-Z]{2}$',
                                'example' => 'BR',
                            ],
                        ),
                        new Parameter(
                            name: 'year',
                            in: 'query',
                            description: 'Year of any of the release dates (1874 to 2100)',
                            required: false,
                            schema: [
                                'type' => 'integer',
                                'minimum' => 1874,
                                'maximum' => 2100,
                            ],
                        ),
                        new Parameter(
                            name: 'primary_release_year',
                            in: 'query',
                            description: 'Year of the primary release date (1874 to 2100)',
                            required: false,
                            schema: [
                                'type' => 'integer',
                                'minimum' => 1874,
                                'maximum' => 2100,
                            ],
                        ),
                    ],
                ),
                output: SearchMoviesOutput::class,
                errors: [],
                name: 'get_search_movies',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class SearchMoviesEntryPoint {
}
