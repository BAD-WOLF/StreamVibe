<?php

namespace App\Controller\Movies {

    use App\Service\TmdbApiService;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\{
        HttpFoundation\Response,
        Routing\Attribute\Route,
        Routing\Requirement\Requirement
    };

    #[Route(path: '/movies', methods: ['GET'])]
    class MoviesController extends AbstractController {

        /**
         * @param string $query
         * @param int $page
         * @param TmdbApiService $tmdbApi
         * @return Response
         * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
         */
        #[Route(path: ['/search/{query}/{page}'], name: 'movies_search', requirements: [
            'query' => Requirement::CATCH_ALL, 'page' => Requirement::DIGITS,
        ])]
        public function MoviesShow(string $query, int $page, TmdbApiService $tmdbApi): Response {
            $movies = $tmdbApi->getMovies(search_query: $query, page: $page);
            return $this->json(data: [
                'movies' => $movies,
            ]);
        }

        /**
         * @param Int $movieId
         * @param \App\Service\TmdbApiService $tmdbApiService
         * @return \Symfony\Component\HttpFoundation\Response
         * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
         */
        #[Route(path: '/details/{movieId}', name: 'movie_details', requirements: [
            'movieId' => Requirement::DIGITS,
        ])]
        public function MovieDetails(int $movieId, TmdbApiService $tmdbApiService): Response {
            $movieDetails = $tmdbApiService->getMovieDetails(movie_id: $movieId);
            $personsFromMovie = $tmdbApiService->getPersonsFromMovie(movie_id: $movieId);
            return $this->json(data: [
                'movieDetails' => $movieDetails,
                'personsFromMovie' => $personsFromMovie,
            ]);
        }

        /**
         * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
         */
        #[Route(path: ['/image/{size}/{endpoint}', '/image/{endpoint}'], name: 'movies_img', requirements: [])]
        public function TmdbImg(?string $size, string $endpoint, TmdbApiService $tmdbApiService): Response {
            // dd($tmdbApiService->tmdb_image($size, $endpoint));
            return new Response(content: $tmdbApiService->tmdb_image(size: $size, endpoint: $endpoint), status: 200,
                                headers: ['Content-Type' => 'image/jpeg']);
        }
    }
}

