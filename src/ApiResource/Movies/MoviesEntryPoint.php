<?php

namespace App\ApiResource\Movies;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Movies\MovieDetailsController;
use App\ApiResource\Movies\Details\MovieDetailsOutput;
use App\Controller\Movies\SearchMoviesController;
use App\ApiResource\Movies\Search\SearchMoviesOutput;
use App\Controller\Movies\MovieImageController;
use App\ApiResource\Movies\Image\MovieImageOutput;
use Symfony\Component\Routing\Requirement\Requirement;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;

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
        new Get(
            uriTemplate: '/movies/image/{size}/{endpoint}',
            controller: MovieImageController::class,
            openapi: new Operation(
                parameters: [
                    (new Parameter(
                        name: 'endpoint',
                        in: 'path',
                        description: 'Identificador do recurso de imagem',
                        required: true,
                        schema: ['type' => 'string',]
                    )),
                    (new Parameter(
                        name: 'size',
                        in: 'path',
                        description: 'Tamanho da imagem (opcional)',
                        required: false,
                        schema: [
                            'type' => 'string',
                            'enum' => [
                                'w92','w154','w185','w342','w500','w780',
                                'w300','w780','w45','w185','h632','original',
                            ],
                            'default' => 'original',
                            'required' => true,
                        ]
                    )),
                ],
            ),
            output: MovieImageOutput::class,
            name: 'get_movies_img'
        ),
    ],
)]
class MoviesEntryPoint {

}