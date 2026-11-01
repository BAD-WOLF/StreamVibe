<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movie\Images;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Movie\Images\Model\GetMovieImagesOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\ParameterSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\ErrorResponseSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\UnauthorizedSchema;
use App\Infrastructure\ApiPlatform\Shared\Routing\RouteRequirement;
use App\Infrastructure\Http\Controller\Movie\Images\GetMovieImagesController;

/**
 * GET /{_locale}/api/movies/{movieId}/images — pôsteres, backdrops e logos
 * de um filme.
 *
 * Classe vazia de propósito: só declara a rota e a documentação. Quem
 * atende é o GetMovieImagesController, e o corpo de sucesso é o
 * GetMovieImagesOutput.
 *
 * NOTA (out/2026):
 * - Era Movies/Images/ImagesEntryPoint; renomeado para espelhar o
 *   DTO/UseCase/Controller (GetMovieImages*).
 * - Respostas de erro com o formato real do ExceptionListener
 *   (ErrorResponseSchema); entraram 401, 502 e 503, o 500 saiu.
 * - `language` documentado com o efeito que ele tem no TMDB para imagens:
 *   filtra pelo idioma (imagens em outros idiomas, ou sem texto, ficam de
 *   fora).
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
                uriTemplate: '/movies/{movieId}/images',
                requirements: [
                    'movieId' => RouteRequirement::INTEGER,
                ],
                status: 200,
                controller: GetMovieImagesController::class,
                openapi: new Operation(
                    operationId: 'GetMovieImages',
                    tags: ['Movies'],
                    responses: [
                        200 => new Response(
                            description: 'OK — backdrops, posters and logos of the movie',
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
                    summary: 'Get movie images (posters, backdrops, logos)',
                    description: 'Retrieves the backdrops, posters and logos of a movie, each with ready-to-use URLs (original, w500 and w780).',
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
                            description: 'Language of the images (ISO 639-1, optionally with region). When given, TMDB returns only the images tagged with that language.',
                            required: false,
                            schema: ParameterSchema::LANGUAGE,
                        ),
                    ],
                ),
                output: GetMovieImagesOutput::class,
                errors: [],
                name: 'get_movie_images',
            ),
        ],
        formats: ['json' => ['application/json']],
    ),
]
final class GetMovieImagesEntryPoint {
}
