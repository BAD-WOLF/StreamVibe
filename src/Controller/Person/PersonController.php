<?php

namespace App\Controller\Person;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\{
    HttpFoundation\Response,
    Routing\Attribute\Route,
    Routing\Requirement\Requirement
};
use App\Service\TmdbApiService;

#[Route('/person', methods: ['GET'])]
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
    #[Route(path: '/movies/{personId}', name: 'movies', requirements: [
        'personId' => Requirement::DIGITS,
    ])]
    public function index(int $personId, TmdbApiService $tmdbApiService): Response
    {
        $moviesFromPerson = $tmdbApiService->getMoviesFromPerson($personId);
        dump($moviesFromPerson);
        return $this->json([
            'moviesFromPerson' => $moviesFromPerson,
        ]);
    }
}
