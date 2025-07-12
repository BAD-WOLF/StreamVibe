<?php

namespace App\Controller\API\Content\Person;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\HttpFoundation\Response;
use App\Service\TmdbApiService;
use App\ApiResource\Person\MoviesFromPerson\MoviesFromPersonEntryPoint;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
#[Route(name: 'movies',
    requirements: [
        'personId' => Requirement::DIGITS,
    ],
    defaults: [
        '_api_resource_class' => MoviesFromPersonEntryPoint::class,
        '_api_operation_name' => 'get_movie_from_person',
    ]
)]
final class PersonController extends AbstractController
{
    /**
     * @param int $personId
     * @param \App\Service\TmdbApiService $tmdbApiService
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function __invoke(int $personId, TmdbApiService $tmdbApiService): Response {
        $personDetails = $tmdbApiService->getPersonDetails($personId);
        $moviesSummary = $tmdbApiService->getMoviesFromPerson($personId);

        return $this->json([
            'personDetails' => $personDetails,
            'moviesSummary' => $moviesSummary,
        ]);
    }
}
