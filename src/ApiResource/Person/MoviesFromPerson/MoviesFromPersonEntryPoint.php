<?php

namespace App\ApiResource\Person\MoviesFromPerson;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\API\Content\Person\PersonController;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/person/movies/{personId}',
            controller: PersonController::class,
            output: MoviesFromPersonOutput::class,
            name: 'get_movie_from_person'
        ),
    ],
    formats: ['json' => ['application/json', 'application/ld+json']]
)]
class MoviesFromPersonEntryPoint {

}
