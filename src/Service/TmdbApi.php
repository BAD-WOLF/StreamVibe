<?php

namespace App\Service {

    use Symfony\Contracts\HttpClient\Exception\{
        ClientExceptionInterface,
        DecodingExceptionInterface,
        RedirectionExceptionInterface,
        ServerExceptionInterface,
        TransportExceptionInterface,
    };
    use Symfony\Contracts\HttpClient\HttpClientInterface;

    class TmdbApi
    {

        /**
         * @param HttpClientInterface $client
         */
        public function __construct(
            private readonly HttpClientInterface $client
        )
        {
            $this->_options = [
                'headers' => [
                    'Authorization' => "Bearer {$_ENV['TMDB_TOKEN']}",
                    'accept' => 'application/json',
                ]
            ];
        }

        private string $_domain = 'https://api.themoviedb.org/3' {
            get {
                return $this->_domain;
            }
            set {
                $this->_domain = $value;
            }
        }

        private array $_options {
            get {
                return $this->_options;
            }
            set {
                $this->_options = $value;
            }
        }

        /**
         *
         * Description: Searches for movies based on a query string
         *
         * @param string $search_query
         * @param $page
         * @return array
         * @throws ClientExceptionInterface
         * @throws DecodingExceptionInterface
         * @throws RedirectionExceptionInterface
         * @throws ServerExceptionInterface
         * @throws TransportExceptionInterface
         */
        public function getMovies(string $search_query, $page): array
        {
            $r = $this->client->request(method: 'GET', url: "{$this->_domain}/search/movie?query=$search_query&include_adult=true&language=en-US&page=$page", options: $this->_options);
            return $r->toArray();
        }

        /**
         *
         * Description: Returns all actors of the movie in question
         *
         * @param int $movie_id
         * @return array
         * @throws ClientExceptionInterface
         * @throws DecodingExceptionInterface
         * @throws RedirectionExceptionInterface
         * @throws ServerExceptionInterface
         * @throws TransportExceptionInterface
         */
        public function getPersonsFromMovie(int $movie_id): array
        {
            $r = $this->client->request(method: 'GET', url: "{$this->_domain}/movie/$movie_id/credits?language=en-US", options: $this->_options);
            return $r->toArray();
        }

        /**
         *
         * Description: Returns all the movies from person
         *
         * @param int $persson_id
         * @return array
         * @throws ClientExceptionInterface
         * @throws DecodingExceptionInterface
         * @throws RedirectionExceptionInterface
         * @throws ServerExceptionInterface
         * @throws TransportExceptionInterface
         */
        public function getMoviesFromPerson(int $persson_id): array
        {
            $r = $this->client->request(method: 'GET', url: "{$this->_domain}/person/$persson_id/movie_credits?language=en-US", options: $this->_options);
            return $r->toArray();
        }

        /**
         *
         * Description: Returns detailed information about a specific movie
         *
         * @param int $movie_id
         * @return array
         * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
         */
        public function getMovieDetails(int $movie_id): array {
            $r = $this->client->request(method: 'GET', url: "{$this->_domain}/movie/$movie_id?language=en-US", options: $this->_options);
            return $r->toArray();
        }
    }
}