<?php

namespace App\Controller\Movies {

    use App\Service\TmdbApi;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\{
        HttpFoundation\Response,
        Routing\Attribute\Route,
        Routing\Requirement\Requirement
    };
    use Symfony\Contracts\HttpClient\Exception\{
        ClientExceptionInterface,
        DecodingExceptionInterface,
        RedirectionExceptionInterface,
        ServerExceptionInterface,
        TransportExceptionInterface
    };


    #[Route(path: '/movies', methods: ['GET'])]
    class MoviesController extends AbstractController {

        /**
         * @param TmdbApi $tmdbApi
         * @return Response
         * @throws ClientExceptionInterface
         * @throws DecodingExceptionInterface
         * @throws RedirectionExceptionInterface
         * @throws ServerExceptionInterface
         * @throws TransportExceptionInterface
         */
        #[Route(path: ['/show/{query}/{page}'], name: 'movies_show', requirements: [
            'query' => Requirement::CATCH_ALL, 'page' => Requirement::DIGITS,
        ])]
        public function MoviesShow(string $query, int $page, TmdbApi $tmdbApi): Response {
            $movies = $tmdbApi->getMovies(search_query: $query, page: $page);
            return $this->render(view: 'movies/index.html.twig', parameters: [
                'movies' => $movies,
            ]);
        }

        /**
         * @param Int $movieId
         * @param \App\Service\TmdbApi $tmdbApi
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
        public function MovieDetails(int $movieId, TmdbApi $tmdbApi): Response {
            $movieDetails = $tmdbApi->getMovieDetails(movie_id: $movieId);
            $personsFromMovie = $tmdbApi->getPersonsFromMovie(movie_id: $movieId);
            return $this->render(view: 'movies/details.html.twig', parameters: [
                'movieDetails' => $movieDetails,
                'personsFromMovie' => $personsFromMovie,
            ]);
        }
    }
}

