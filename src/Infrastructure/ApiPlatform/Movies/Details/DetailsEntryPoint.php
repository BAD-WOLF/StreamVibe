<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Movies\Details;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Movies\Details\Model\MovieDetailsOutput;
use App\Infrastructure\ApiPlatform\Movies\Details\Schema\MovieNotFoundSchema;
use App\Infrastructure\ApiPlatform\Movies\Details\Schema\MovieDetailsErrorSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\InvalidMovieIdSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\MovieApiErrorSchema;
use App\Infrastructure\Http\Controller\Movie\Details\GetMovieDetailsController;

/**
 *
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/movies/{movieId}',
                requirements: [
                    'movieId' => '\d+',
                ],
                status: 200,
                controller: GetMovieDetailsController::class,
                openapi: new Operation(
                    operationId: 'GetMovieDetails',
                    tags: ['Movies'],
                    responses: [
                        400 => new Response(
                            description: "Bad Request — Invalid movie ID provided",
                            content: new InvalidMovieIdSchema(),
                        ),
                        404 => new Response(
                            description: "Not Found — Movie not found",
                            content: new MovieNotFoundSchema(),
                        ),
                        422 => new Response(
                            description: "Unprocessable Entity — Movie details processing failed",
                            content: new MovieDetailsErrorSchema(),
                        ),
                        500 => new Response(
                            description: "Internal Server Error — Unexpected failure while fetching movie details",
                            content: new MovieApiErrorSchema(
                                exampleMessage: "An error occurred while fetching movie details",
                            ),
                        ),
                    ],
                    summary: "Get detailed movie information",
                    description: "Retrieves comprehensive information about a specific movie including basic details, genres, ratings, and optionally credits, videos, images, recommendations, and similar movies",
                    parameters: [
                        new Parameter(
                            name: "_locale",
                            in: "path",
                            required: true,
                            schema: [
                                "type" => "string",
                                "default" => "pt_BR",
                            ],
                        ),
                        new Parameter(
                            name: "movieId",
                            in: "path",
                            description: "The ID of the movie to retrieve details for",
                            required: true,
                            schema: [
                                "type" => "integer",
                                "format" => "int64",
                                "minimum" => 1,
                            ],
                        ),
                    ],
                ),
                output: MovieDetailsOutput::class,
                name: "get_movie_details",
            ),
        ],
    ),
]
class DetailsEntryPoint
{
    // Empty class, because "Movie Details" is just a logical grouping.
// Its input and output are defined separately.
}
