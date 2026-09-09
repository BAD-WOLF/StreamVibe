<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Movies\Videos;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Movies\Videos\Model\VideosOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\InvalidMovieIdSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\MovieContentNotFoundSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\MovieApiErrorSchema;
use App\Infrastructure\Http\Controller\Movie\Videos\GetMovieVideosController;

/**
 *
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: "/movies/{movieId}/videos",
                requirements: [
                    "movieId" => "\d+",
                ],
                status: 200,
                controller: GetMovieVideosController::class,
                openapi: new Operation(
                    operationId: "GetMovieVideos",
                    tags: ["Movies"],
                    responses: [
                        400 => new Response(
                            description: "Bad Request — Invalid movie ID provided",
                            content: new InvalidMovieIdSchema(),
                        ),
                        404 => new Response(
                            description: "Not Found — Videos not found for the specified movie",
                            content: new MovieContentNotFoundSchema(
                                "Videos not found for the specified movie",
                                "videos",
                            ),
                        ),
                        500 => new Response(
                            description: "Internal Server Error — Unexpected failure while fetching movie videos",
                            content: new MovieApiErrorSchema(
                                "An error occurred while fetching movie videos",
                            ),
                        ),
                    ],
                    summary: "Get movie videos (trailers, teasers, clips)",
                    description: "Retrieves video content for a specific movie including trailers, teasers, and clips",
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
                            description: "The ID of the movie to retrieve videos for",
                            required: true,
                            schema: [
                                "type" => "integer",
                                "format" => "int64",
                                "minimum" => 1,
                            ],
                        ),
                    ],
                ),
                output: VideosOutput::class,
                name: "get_movie_videos",
            ),
        ],
    ),
]
class VideosEntryPoint
{
    // Empty class, because "Movie Videos" is just a logical grouping.
// Its input and output are defined separately.
}
