<?php

namespace App\Controller\Movies;

use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\HttpFoundation\Response;
use App\Service\TmdbApiService;
use App\ApiResource\Movies\MoviesEntryPoint;

#[AsController]
#[Route(name: 'api_movies_search',
    requirements: [
        'query' => Requirement::CATCH_ALL,
        'page' => Requirement::DIGITS,
    ],
    defaults: [
        '_api_resource_class' => MoviesEntryPoint::class,
        '_api_operation_name' => 'get_movies_search',
    ], methods: ['GET'])]
class SearchMoviesController extends AbstractController {
    /**
     * @param string                      $query
     * @param int                         $page
     * @param \App\Service\TmdbApiService $tmdbApi
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function __invoke(string $query, int $page, TmdbApiService $tmdbApi): Response {
        $movies = $tmdbApi->getMovies(search_query: $query, page: $page);

        return $this->json([
            'movies' => $movies,
        ]);
    }
}