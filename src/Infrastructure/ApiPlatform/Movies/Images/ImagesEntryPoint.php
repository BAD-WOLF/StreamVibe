<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Movies\Images;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Infrastructure\ApiPlatform\Movies\Images\Model\ImagesOutput;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\InvalidMovieIdSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\MovieContentNotFoundSchema;
use App\Infrastructure\ApiPlatform\Shared\OpenApi\Schema\MovieApiErrorSchema;
use App\Infrastructure\Http\Controller\Movie\Images\GetMovieImagesController;

/**
 *
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: "/movies/{movieId}/images",
                requirements: [
                    "movieId" => "\d+",
                ],
                status: 200,
                controller: GetMovieImagesController::class,
                openapi: new Operation(
                    operationId: "GetMovieImages",
                    tags: ["Movies"],
                    responses: [
                        400 => new Response(
                            description: "Bad Request — Invalid movie ID provided",
                            content: new InvalidMovieIdSchema(),
                        ),
                        404 => new Response(
                            description: "Not Found — Images not found for the specified movie",
                            content: new MovieContentNotFoundSchema(
                                "Images not found for the specified movie",
                                "images",
                            ),
                        ),
                        500 => new Response(
                            description: "Internal Server Error — Unexpected failure while fetching movie images",
                            content: new MovieApiErrorSchema(
                                "An error occurred while fetching movie images",
                            ),
                        ),
                    ],
                    summary: "Get movie images (posters, backdrops, logos)",
                    description: "Retrieves image assets for a specific movie including posters, backdrops, and logos",
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
                            description: "The ID of the movie to retrieve images for",
                            required: true,
                            schema: [
                                "type" => "integer",
                                "format" => "int64",
                                "minimum" => 1,
                            ],
                        ),
                    ],
                ),
                output: ImagesOutput::class,
                name: "get_movie_images",
            ),
        ],
    ),
]
class ImagesEntryPoint
{
    // Empty class, because "Movie Images" is just a logical grouping.
// Its input and output are defined separately.
}
