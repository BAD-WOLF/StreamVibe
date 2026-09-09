<?php
declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Person\Details;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Infrastructure\ApiPlatform\Person\Details\Model\PersonDetailsOutput;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use App\Infrastructure\Http\Controller\Person\PersonDetailsController;

/**
 * NOTA (ago/2026): language/region são aceitos e validados, mas
 * GetPersonDetailsUseCase nunca os passa pra
 * TmdbApiService::getPersonDetails() (chamada com só $personId) — ou
 * seja, hoje não têm efeito real na resposta. Documentados aqui como
 * opcionais porque existem no contrato do Request; revisar depois se
 * devem ser conectados de verdade ou removidos.
 */
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/person/{personId}/details',
            requirements: [
                'personId' => '\d+',
            ],
            status: 200,
            controller: PersonDetailsController::class,
            openapi: new Operation(
                parameters: [
                    new Parameter(
                        name: 'personId',
                        in: 'path',
                        required: true,
                        schema: [
                            'type' => 'integer',
                            'description' => 'Person ID from TMDB'
                        ]
                    ),
                    new Parameter(
                        name: 'language',
                        in: 'query',
                        required: false,
                        schema: [
                            'type' => 'string',
                        ],
                        description: 'ISO 639-1 language code (currently not connected to the TMDB call)',
                    ),
                    new Parameter(
                        name: 'region',
                        in: 'query',
                        required: false,
                        schema: [
                            'type' => 'string',
                        ],
                        description: 'ISO 3166-1 region code (currently not connected to the TMDB call)',
                    ),
                ]
            ),
            output: PersonDetailsOutput::class,
            name: 'get_movie_person_details',
        ),
    ],
)]
final class PersonDetailsEntryPoint {
    // Empty class, because "Person" is just a logical grouping.
    // Its operations are defined separately above.
}