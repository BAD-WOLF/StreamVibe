<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Movies\Similar;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Movies\Similar\Model\SimilarOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\InvalidMovieIdSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\MovieContentNotFoundSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\MovieApiErrorSchema;
use App\Infrastructure\Http\Controller\Movie\Similar\GetMovieSimilarController;

/**
 *
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: "/movies/{movieId}/similar",
                requirements: [
                    "movieId" => "\d+",
                ],
                status: 200,
                controller: GetMovieSimilarController::class,
                openapi: new Operation(
                    operationId: 'GetMovieSimilar',
                    tags: ['Movies'],
                    responses: [
                        400 => new Response(
                            description: "Bad Request — Invalid movie ID provided",
                            content: new InvalidMovieIdSchema(),
                        ),
                        404 => new Response(
                            description: "Not Found — Similar movies not found for the specified movie",
                            content: new MovieContentNotFoundSchema(
                                exampleMessage: "Similar movies not found for the specified movie",
                                contentType: "similar",
                            ),
                        ),
                        500 => new Response(
                            description: "Internal Server Error — Unexpected failure while fetching similar movies",
                            content: new MovieApiErrorSchema(
                                exampleMessage: "An error occurred while fetching similar movies",
                            ),
                        ),
                    ],
                    summary: "Get similar movies",
                    description: "Retrieves a list of movies similar to the specified movie based on genres, themes, and other factors",
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
                            description: "The ID of the movie to find similar movies for",
                            required: true,
                            schema: [
                                "type" => "integer",
                                "format" => "int64",
                                "minimum" => 1,
                            ],
                        ),
                    ],
                ),
                output: SimilarOutput::class,
                name: "get_movie_similar",
            ),
        ],
    ),
]
class SimilarEntryPoint
{
    // Empty class, because "Movie Similar" is just a logical grouping.
// Its input and output are defined separately.
}
