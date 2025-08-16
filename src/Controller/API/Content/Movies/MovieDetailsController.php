<?php

namespace App\Controller\API\Content\Movies;

use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\TmdbApiService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Requirement\Requirement;
use App\ApiResource\Movies\MoviesEntryPoint;

#[AsController]
#[Route(name: 'api_movie_details',
    requirements: [
        'movieId' => Requirement::DIGITS,
    ], defaults: [
        '_api_resource_class' => MoviesEntryPoint::class,
        '_api_operation_name' => 'get_movie_details',
    ], methods: ['GET']
)]
class MovieDetailsController extends AbstractController {
    /**
     * @param int                         $movieId
     * @param \App\Service\TmdbApiService $tmdbApiService
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function __invoke(int $movieId, TmdbApiService $tmdbApiService): Response {
        $movieDetails = $tmdbApiService->getMovieDetails(movie_id: $movieId);
        $personSummary = $tmdbApiService->getPersonsFromMovie(movie_id: $movieId);

        return $this->json([
            'movieDetails' => $movieDetails,
            'personSummary' => $personSummary,
        ]);
    }
}