<?php
declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Person\Credits;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use App\Infrastructure\ApiPlatform\Person\Credits\Model\PersonMovieCreditsOutput;
use App\Infrastructure\Http\Controller\Person\PersonMovieCreditsController;

/**
 * Endpoint dedicado pra créditos de filme de uma pessoa, separado do
 * endpoint principal de detalhes (ago/2026).
 */
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/person/{personId}/movie/credits',
            requirements: [
                'personId' => '\d+',
            ],
            status: 200,
            controller: PersonMovieCreditsController::class,
            openapi: new Operation(
                parameters: [
                    new Parameter(
                        name: 'personId',
                        in: 'path',
                        required: true,
                        schema: [
                            'type' => 'integer',
                            'description' => 'Person ID from TMDB',
                        ],
                    ),
                ],
            ),
            output: PersonMovieCreditsOutput::class,
            name: 'get_person_movie_credits',
        ),
    ],
)]
final class PersonMovieCreditsEntryPoint {
    // Empty class, because this is just a logical grouping.
}