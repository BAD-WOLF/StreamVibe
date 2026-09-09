<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Movies\Credits;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Movies\Credits\Model\CreditsOutput;
use App\Infrastructure\ApiPlatform\Movies\Credits\Schema\CreditsNotFoundSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\InvalidMovieIdSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\MovieApiErrorSchema;
use App\Infrastructure\Http\Controller\Movie\Credits\GetMovieCreditsController;

/**
 *
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: "/movies/{movieId}/credits",
                requirements: [
                    "movieId" => "\d+",
                ],
                status: 200,
                controller: GetMovieCreditsController::class,
                openapi: new Operation(
                    operationId: "GetMovieCredits",
                    tags: ["Movies"],
                    responses: [
                        400 => new Response(
                            description: "Bad Request — Invalid movie ID provided",
                            content: new InvalidMovieIdSchema(),
                        ),
                        404 => new Response(
                            description: "Not Found — Credits not found for the specified movie",
                            content: new CreditsNotFoundSchema(),
                        ),
                        500 => new Response(
                            description: "Internal Server Error — Unexpected failure while fetching movie credits",
                            content: new MovieApiErrorSchema(
                                "An error occurred while fetching movie credits",
                            ),
                        ),
                    ],
                    summary: "Get movie credits (cast and crew)",
                    description: "Retrieves cast and crew information for a specific movie",
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
                            description: "The ID of the movie to retrieve credits for",
                            required: true,
                            schema: [
                                "type" => "integer",
                                "format" => "int64",
                                "minimum" => 1,
                            ],
                        ),
                    ],
                ),
                output: CreditsOutput::class,
                name: "get_movie_credits",
            ),
        ],
    ),
]
class CreditsEntryPoint
{
    // Empty class, because "Movie Credits" is just a logical grouping.
// Its input and output are defined separately.
}
