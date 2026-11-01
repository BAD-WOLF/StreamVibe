<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movie\Details;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Movie\Details\Model\GetMovieDetailsOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\Shared\Routing\RouteRequirement;
use App\Infrastructure\Http\Controller\Movie\Details\GetMovieDetailsController;

/**
 * GET /{_locale}/api/movies/{movieId} — detalhes de um filme.
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o GetMovieDetailsController, e o corpo de sucesso é o
 * GetMovieDetailsOutput.
 *
 * NOTA (out/2026):
 * - Era Movies/Details/DetailsEntryPoint; renomeado para espelhar o
 *   DTO/UseCase/Controller (GetMovieDetails*).
 * - Respostas de erro reescritas com o formato real do ExceptionListener
 *   (ErrorResponseSchema). Saíram o 422 (nenhum caminho do código o
 *   devolve) e o 500 "documentado" como resposta normal; entraram o 401
 *   (JWT), o 404 de filme inexistente (antes o TMDB 404 virava 400) e os
 *   502/503 do TMDB.
 * - A descrição prometia "optionally credits, videos, images,
 *   recommendations and similar movies", que saíram daqui em ago/2026
 *   (cada um tem endpoint próprio).
 * - `language` documentado e repassado ao TMDB.
 * - Requirement numérico via RouteRequirement::INTEGER (até 18 dígitos):
 *   com '\d+', um número que não cabe em int chegava ao controller e
 *   virava TypeError (500).
 * - formats json e errors: [] pelo mesmo motivo do SearchMoviesEntryPoint
 *   (o controller responde application/json, e os erros reais estão em
 *   `responses`).
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/movies/{movieId}',
                requirements: [
                    'movieId' => RouteRequirement::INTEGER,
                ],
                status: 200,
                controller: GetMovieDetailsController::class,
                openapi: new Operation(
                    operationId: 'GetMovieDetails',
                    tags: ['Movies'],
                    responses: [
                        200 => new Response(
                            description: 'OK — details of the movie',
                        ),
                        400 => new Response(
                            description: 'Bad Request — movieId out of range or malformed language',
                            content: new ErrorResponseSchema(
                                type: 'user_validation_error',
                                message: 'Multiple validation errors occurred',
                                errors: ['Movie_id: Movie ID must be between 1 and 999999999'],
                            ),
                        ),
                        401 => new Response(
                            description: 'Unauthorized — JWT missing, invalid or expired',
                            content: new UnauthorizedSchema(),
                        ),
                        404 => new Response(
                            description: 'Not Found — the movie does not exist on TMDB',
                            content: new ErrorResponseSchema(
                                type: 'business_logic_error',
                                message: 'Movie with identifier "999999999" not found',
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
                    summary: 'Get detailed movie information',
                    description: 'Retrieves the details of a movie (genres, collection, production companies, runtime...) plus derived fields: poster_url, backdrop_url, thumbnail_url, release_year, rating_percentage and formatted_runtime.',
                    parameters: [
                        new Parameter(
                            name: '_locale',
                            in: 'path',
                            description: ParameterSchema::LOCALE_DESCRIPTION,
                            required: true,
                            schema: ParameterSchema::LOCALE,
                        ),
                        new Parameter(
                            name: 'movieId',
                            in: 'path',
                            description: 'TMDB ID of the movie',
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
                output: GetMovieDetailsOutput::class,
                errors: [],
                name: 'get_movie_details',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class GetMovieDetailsEntryPoint {
}
