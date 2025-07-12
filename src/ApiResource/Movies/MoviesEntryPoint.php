<?php

namespace App\ApiResource\Movies;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\ApiResource\Movies\Details\MovieDetailsOutput;
use App\ApiResource\Movies\Search\SearchMoviesOutput;
use Symfony\Component\Routing\Requirement\Requirement;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use App\Controller\API\Content\Movies\SearchMoviesController;
use App\Controller\API\Content\Movies\MovieDetailsController;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/movies/details/{movieId}',
            requirements: ['movieId' => Requirement::DIGITS],
            controller: MovieDetailsController::class,
            openapi: new Operation(
                parameters: [
                    (new Parameter(
                        name: 'movieId',
                        in: 'path',
                        required: true,
                        schema: ['type' => 'integer'],
                    ))
                ]
            ),
            output: MovieDetailsOutput::class,
            name: 'get_movie_details'
        ),
        new Get(
            uriTemplate: '/movies/search/{query}/{page}',
            controller: SearchMoviesController::class,
            openapi: new Operation(
                parameters: [
                    (new Parameter(
                        name: 'page',
                        in: 'path',
                        required: true,
                        schema: ['type' => 'integer', 'default' => 1],
                    ))
                ]
            ),
            output: SearchMoviesOutput::class,
            name: 'get_movies_search'
        ),
    ],
)]
class MoviesEntryPoint {

}