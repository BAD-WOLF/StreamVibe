<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movie\Videos;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Movie\Videos\Model\GetMovieVideosOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\Shared\Routing\RouteRequirement;
use App\Infrastructure\Http\Controller\Movie\Videos\GetMovieVideosController;

/**
 * GET /{_locale}/api/movies/{movieId}/videos — trailers, teasers, clips e
 * demais vídeos de um filme.
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o GetMovieVideosController, e o corpo de sucesso é o
 * GetMovieVideosOutput.
 *
 * NOTA (out/2026):
 * - Era Movies/Videos/VideosEntryPoint; renomeado para espelhar o
 *   DTO/UseCase/Controller (GetMovieVideos*).
 * - Respostas de erro com o formato real do ExceptionListener
 *   (ErrorResponseSchema); entraram 401, 502 e 503, o 500 saiu.
 * - `language` documentado com o efeito que ele tem no TMDB para vídeos:
 *   filtra pelo idioma (sem ele, o TMDB usa en-US).
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
                uriTemplate: '/movies/{movieId}/videos',
                requirements: [
                    'movieId' => RouteRequirement::INTEGER,
                ],
                status: 200,
                controller: GetMovieVideosController::class,
                openapi: new Operation(
                    operationId: 'GetMovieVideos',
                    tags: ['Movies'],
                    responses: [
                        200 => new Response(
                            description: 'OK — videos of the movie, also split into trailers, teasers and clips',
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
                    summary: 'Get movie videos (trailers, teasers, clips)',
                    description: 'Retrieves the videos of a movie; trailers, teasers and clips also come in their own lists, and YouTube videos come with watch and embed URLs.',
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
                            description: 'Language of the videos (ISO 639-1, optionally with region). TMDB returns only the videos in that language; without it TMDB uses en-US.',
                            required: false,
                            schema: ParameterSchema::LANGUAGE,
                        ),
                    ],
                ),
                output: GetMovieVideosOutput::class,
                errors: [],
                name: 'get_movie_videos',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class GetMovieVideosEntryPoint {
}
