<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movies\Search;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Infrastructure\ApiPlatform\Movies\Search\Model\SearchMoviesOutput;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use App\Infrastructure\Http\Controller\Movie\NowPlayingMoviesController;
use App\Infrastructure\Http\Controller\Movie\TopRatedMoviesController;
use App\Infrastructure\Http\Controller\Movie\PopularMoviesController;
use App\Infrastructure\Http\Controller\Movie\TrendingMoviesController;
use App\Infrastructure\Http\Controller\Movie\SearchMoviesController;

/**
 *
 */
#[
    ApiResource(
        operations: [
            new Get(
                uriTemplate: '/movies/search/{query}/{page}',
                requirements: [
                    'page' => '\d+',
                    // Antes era '.+' (exige 1+ caracteres), o que fazia o
                    // roteador devolver 404 para query vazia (//1) antes
                    // mesmo de chegar no controller. A validação de 400
                    // ("query" obrigatória) já existe em
                    // SearchMoviesController::__invoke() — agora ela
                    // consegue ser alcançada.
                    'query' => '.*',
                ],
                status: 200,
                controller: SearchMoviesController::class,
                openapi: new Operation(
                    operationId: 'GetMovieSearch',
                    tags: ['Movies'],
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
                            name: 'query',
                            in: 'path',
                            required: true,
                            schema: [
                                'type' => 'string',
                                'description' => 'Search query for movies',
                            ],
                        ),
                        new Parameter(
                            name: 'page',
                            in: 'path',
                            required: false,
                            schema: [
                                'type' => 'integer',
                                'default' => 1,
                                'description' => 'Page number'
                            ]
                        )
                    ],
                ),
                output: SearchMoviesOutput::class,
                name: 'get_search_movies',
            ),
            new Get(
                uriTemplate: '/movies/trending/{timeWindow}',
                requirements: [
                    'timeWindow' => 'day|week',
                ],
                status: 200,
                controller: TrendingMoviesController::class,
                openapi: new Operation(
                    operationId: 'GetMovieTrending',
                    tags: ['Movies'],
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
                            name: 'timeWindow',
                            in: 'path',
                            required: true,
                            schema: [
                                'type' => 'string',
                                'enum' => ['day', 'week'],
                                'default' => 'week',
                                'description' =>
                                    'Time window for trending movies',
                            ],
                        ),
                    ],
                ),
                output: SearchMoviesOutput::class,
                name: 'get_trending_movies',
            ),
            new Get(
                uriTemplate: '/movies/popular',
                status: 200,
                controller: PopularMoviesController::class,
                openapi: new Operation(
                    operationId: 'GetMoviePopular',
                    tags: ['Movies'],
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
                output: SearchMoviesOutput::class,
                name: 'get_popular_movies',
            ),
            new Get(
                uriTemplate: '/movies/top-rated',
                status: 200,
                controller: TopRatedMoviesController::class,
                openapi: new Operation(
                    operationId: 'GetMovieTopRated',
                    tags: ['Movies'],
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
                output: SearchMoviesOutput::class,
                name: 'get_top_rated_movies',
            ),
            new Get(
                uriTemplate: '/movies/now-playing',
                status: 200,
                controller: NowPlayingMoviesController::class,
                openapi: new Operation(
                    operationId: 'GetMovieNowPlaying',
                    tags: ['Movies'],
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
                output: SearchMoviesOutput::class,
                name: 'get_now_playing_movies',
            ),
        ],
    ),
]
final class MoviesEntryPoint {
    // Empty class, because "Movies" is just a logical grouping.
    // Its operations are defined separately above.
}
