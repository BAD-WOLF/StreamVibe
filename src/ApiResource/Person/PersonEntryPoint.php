<?php

namespace App\ApiResource\Person;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\API\Content\Person\PersonController;
use App\ApiResource\Person\Details\Model\PersonDetailsOutput;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/person/movies/{personId}',
            controller: PersonController::class,
            output: PersonDetailsOutput::class,
            name: 'get_movie_from_person'
        ),
    ],
    formats: ['json' => ['application/json', 'application/ld+json']]
)]
class PersonEntryPoint {

}
