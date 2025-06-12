<?php

declare(strict_types = 1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\TmdbApiService;

class HomeController extends AbstractController {
    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     */
    #[Route(path: ['/', '/home',], name: 'app_home', methods: ['GET'])]
    public function index(TmdbApiService $tmdbApiService): Response {
        return $this->render('home/index.html.twig', parameters: [
            'playing' => $tmdbApiService->getNowPlaying(),
            'popular' => $tmdbApiService->getPopular(),
            'topRated' => $tmdbApiService->getTopRated(),
            'upcoming' => $tmdbApiService->getUpcoming(),
        ]);
    }
}
