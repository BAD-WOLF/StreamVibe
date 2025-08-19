<?php

namespace App\ApiResource\Person;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\API\Content\Person\PersonController;
use App\ApiResource\Person\Details\Model\PersonDetailsOutput;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/person/movies/{personId}',
            controller: PersonController::class,
            openapi: new Operation(
                parameters: [
                    (new Parameter(
                        name: '_locale',
                        in: 'path',
                        required: true,
                        schema: [
                            'type' => 'string',
                            'default' => 'pt_BR',
                        ]
                    )),
                ]
            ),
            output: PersonDetailsOutput::class,
            name: 'get_movie_from_person',
        ),
    ],
    formats: ['json' => ['application/json', 'application/ld+json']]
)]
class PersonEntryPoint {

}
