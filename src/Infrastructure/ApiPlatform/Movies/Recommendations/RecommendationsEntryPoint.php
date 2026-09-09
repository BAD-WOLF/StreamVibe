<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Movies\Recommendations;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Movies\Recommendations\Model\RecommendationsOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\InvalidMovieIdSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\MovieContentNotFoundSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\MovieApiErrorSchema;
use App\Infrastructure\Http\Controller\Movie\Recommendations\GetMovieRecommendationsController;

/**
 *
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: "/movies/{movieId}/recommendations",
                requirements: [
                    "movieId" => "\d+",
                ],
                status: 200,
                controller: GetMovieRecommendationsController::class,
                openapi: new Operation(
                    operationId: "GetMovieRecommendations",
                    tags: ["Movies"],
                    responses: [
                        400 => new Response(
                            description: "Bad Request — Invalid movie ID provided",
                            content: new InvalidMovieIdSchema(),
                        ),
                        404 => new Response(
                            description: "Not Found — Recommendations not found for the specified movie",
                            content: new MovieContentNotFoundSchema(
                                "Recommendations not found for the specified movie",
                                "recommendations",
                            ),
                        ),
                        500 => new Response(
                            description: "Internal Server Error — Unexpected failure while fetching movie recommendations",
                            content: new MovieApiErrorSchema(
                                "An error occurred while fetching movie recommendations",
                            ),
                        ),
                    ],
                    summary: "Get movie recommendations",
                    description: "Retrieves recommended movies based on the specified movie",
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
                            description: "The ID of the movie to get recommendations for",
                            required: true,
                            schema: [
                                "type" => "integer",
                                "format" => "int64",
                                "minimum" => 1,
                            ],
                        ),
                    ],
                ),
                output: RecommendationsOutput::class,
                name: "get_movie_recommendations",
            ),
        ],
    ),
]
class RecommendationsEntryPoint
{
    // Empty class, because "Movie Recommendations" is just a logical grouping.
// Its input and output are defined separately.
}
