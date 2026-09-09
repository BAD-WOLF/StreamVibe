<?php
declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Image;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Infrastructure\ApiPlatform\Image\Model\ImageOutput;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use App\Infrastructure\Http\Controller\Image\ImageInfoController;
use App\Infrastructure\Http\Controller\Image\ImageSizesController;
use App\Infrastructure\Http\Controller\Image\ImageOriginalController;
use App\Infrastructure\Http\Controller\Image\ImageController;

/**
 *
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/image/{size}/{endpoint}',
                requirements: [
                    "size" =>
                        "w92|w154|w185|w342|w500|w780|w1280|h632|original",
                    "endpoint" => ".+",
                ],
                status: 200,
                controller: ImageController::class,
                openapi: new Operation(
                    parameters: [
                        new Parameter(
                            name: '_locale',
                            in: 'path',
                            required: true,
                            schema: [
                                'type' => 'string',
                                'default' => 'pt_BR',
                            ],
                        ),
                        new Parameter(
                            name: 'size',
                            in: 'path',
                            required: true,
                            schema: [
                                'type' => 'string',
                                'enum' => [
                                    'w92',
                                    'w154',
                                    'w185',
                                    'w342',
                                    'w500',
                                    'w780',
                                    'w1280',
                                    'h632',
                                    'original',
                                ],
                                'description' =>
                                    'Image size (w92, w154, w185, w342, w500, w780, w1280, h632, original)',
                            ],
                        ),
                        new Parameter(
                            name: 'endpoint',
                            in: 'path',
                            required: true,
                            schema: [
                                'type' => 'string',
                                'description' => 'TMDB image endpoint path',
                            ],
                        ),
                    ],
                ),
                output: ImageOutput::class,
                name: 'get_image_with_size',
            ),
            new Get(
                uriTemplate: '/image/{endpoint}',
                requirements: [
                    "endpoint" => ".+",
                ],
                status: 200,
                controller: ImageOriginalController::class,
                openapi: new Operation(
                    parameters: [
                        new Parameter(
                            name: '_locale',
                            in: 'path',
                            required: true,
                            schema: [
                                'type' => 'string',
                                'default' => 'pt_BR',
                            ],
                        ),
                        new Parameter(
                            name: 'endpoint',
                            in: 'path',
                            required: true,
                            schema: [
                                'type' => 'string',
                                'description' => 'TMDB image endpoint path',
                            ],
                        ),
                    ],
                ),
                output: ImageOutput::class,
                name: 'get_image_original',
            ),
            new Get(
                uriTemplate: '/images/sizes',
                status: 200,
                controller: ImageSizesController::class,
                openapi: new Operation(
                    parameters: [
                        new Parameter(
                            name: '_locale',
                            in: 'path',
                            required: true,
                            schema: [
                                'type' => 'string',
                                'default' => 'pt_BR',
                            ],
                        ),
                    ],
                ),
                name: 'get_image_sizes',
            ),
            new Get(
                uriTemplate: '/images/info/{endpoint}',
                requirements: [
                    "endpoint" => ".+",
                ],
                status: 200,
                controller: ImageInfoController::class,
                openapi: new Operation(
                    parameters: [
                        new Parameter(
                            name: '_locale',
                            in: 'path',
                            required: true,
                            schema: [
                                'type' => 'string',
                                'default' => 'pt_BR',
                            ],
                        ),
                        new Parameter(
                            name: 'endpoint',
                            in: 'path',
                            required: true,
                            schema: [
                                'type' => 'string',
                                'description' => 'TMDB image endpoint path',
                            ],
                        ),
                    ],
                ),
                output: ImageOutput::class,
                name: 'get_image_info',
            ),
        ],
    ),
]
final class ImageEntryPoint {
    // Empty class, because "Image" is just a logical grouping.
    // Its operations are defined separately above.
}
