<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movie\Popular;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Movie\Popular\Model\GetPopularMoviesOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\Http\Controller\Movie\Popular\GetPopularMoviesController;

/**
 * GET /{_locale}/api/movies/popular — filmes populares no TMDB.
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o GetPopularMoviesController, e o corpo de sucesso é o
 * GetPopularMoviesOutput.
 *
 * NOTA (out/2026):
 * - Saiu do antigo Movies/Search/MoviesEntryPoint (uma EntryPoint por
 *   endpoint, espelhando Application/Movie/{DTO,UseCase}/Popular e
 *   Controller/Movie/Popular).
 * - `page` e `language` documentados; `language` agora chega ao TMDB.
 * - formats json e errors: [] pelo mesmo motivo do SearchMoviesEntryPoint
 *   (o controller responde application/json, e os erros reais estão em
 *   `responses`).
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/movies/popular',
                status: 200,
                controller: GetPopularMoviesController::class,
                openapi: new Operation(
                    operationId: 'GetMoviePopular',
                    tags: ['Movies'],
                    responses: [
                        200 => new Response(
                            description: 'OK — page of popular movies',
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
                    summary: 'Get popular movies',
                    description: 'Lists the movies currently popular on TMDB.',
                    parameters: [
                        new Parameter(
                            name: '_locale',
                            in: 'path',
                            description: ParameterSchema::LOCALE_DESCRIPTION,
                            required: true,
                            schema: ParameterSchema::LOCALE,
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
                output: GetPopularMoviesOutput::class,
                errors: [],
                name: 'get_popular_movies',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class GetPopularMoviesEntryPoint {
}
