<?php

declare(strict_types = 1);

namespace App\Service {

    use Symfony\Contracts\HttpClient\Exception\{
        ClientExceptionInterface,
        DecodingExceptionInterface,
        RedirectionExceptionInterface,
        ServerExceptionInterface,
        TransportExceptionInterface,
    };
    use Symfony\Contracts\HttpClient\HttpClientInterface;

    class TmdbApiService {

        /**
         * @param HttpClientInterface $client
         */
        public function __construct(
            private readonly HttpClientInterface $client
        ) {
            $this->_options = [
                'headers' => [
                    'Authorization' => "Bearer {$_ENV['TMDB_TOKEN']}",
                    'accept' => 'application/json',
                ]
            ];
        }

        private string $_domain_api = 'https://api.themoviedb.org/3' {
            get {
                return $this->_domain_api;
            }
            set {
                $this->_domain_api = $value;
            }
        }

        private string $_domain_img = 'https://image.tmdb.org/t/p/' {
            get {
                return $this->_domain_img;
            }
            set(string $value) {
                $this->_domain_img = $value;
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
         * @param        $page
         *
         * @return array
         * @throws ClientExceptionInterface
         * @throws DecodingExceptionInterface
         * @throws RedirectionExceptionInterface
         * @throws ServerExceptionInterface
         * @throws TransportExceptionInterface
         */
        public function getMovies(string $search_query, $page): array {
            $r = $this->client->request(
                method: 'GET',
                url: "{$this->_domain_api}/search/movie?query=$search_query&include_adult=true&page=$page",
                options: $this->_options
            );

            return $r->toArray();
        }

        /**
         *
         * Description: Returns detailed information about a specific movie
         *
         * @param int $movie_id
         *
         * @return array
         * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
         */
        public function getMovieDetails(int $movie_id): array {
            $r = $this->client->request(
                method: 'GET',
                url: "{$this->_domain_api}/movie/$movie_id",
                options: $this->_options
            );

            return $r->toArray();
        }

        /**
         *
         * Description: Returns all the movies from person
         *
         * @param int $movie_id
         * @return array
         * @throws ClientExceptionInterface
         * @throws DecodingExceptionInterface
         * @throws RedirectionExceptionInterface
         * @throws ServerExceptionInterface
         * @throws TransportExceptionInterface
         */
        public function getPersonsFromMovie(int $movie_id): array {
            $r = $this->client->request(
                method: 'GET',
                url: "{$this->_domain_api}/movie/$movie_id/credits",
                options: $this->_options
            );

            return $r->toArray();
        }

        /**
         * @param int $person_id
         *
         * @return array
         * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
         */
        public function getPersonDetails(int $person_id): array {
            $r = $this->client->request(
                method: 'GET',
                url: "{$this->_domain_api}/person/$person_id",
                options: $this->_options
            );

            return $r->toArray();
        }

        /**
         *
         * Description: Returns all actors of the movie in question
         *
         * @param int $persson_id
         * @return array
         * @throws ClientExceptionInterface
         * @throws DecodingExceptionInterface
         * @throws RedirectionExceptionInterface
         * @throws ServerExceptionInterface
         * @throws TransportExceptionInterface
         */
        public function getMoviesFromPerson(int $persson_id): array {
            $r = $this->client->request(
                method: 'GET',
                url: "{$this->_domain_api}/person/$persson_id/movie_credits",
                options: $this->_options
            );

            return $r->toArray();
        }

        /**
         * @return array
         * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
         */
        public function getNowPlaying(): array {
            $r = $this->client->request(
                method: 'GET',
                url: "{$this->_domain_api}/movie/now_playing",
                options: $this->_options
            );

            return $r->toArray();
        }

        /**
         * @return array
         * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
         */
        public function getPopular(): array {
            $r = $this->client->request(
                method: 'GET',
                url: "{$this->_domain_api}/movie/popular",
                options: $this->_options
            );

            return $r->toArray();
        }

        /**
         * @return array
         * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
         */
        public function getTopRated(): array {
            $r = $this->client->request(
                method: 'GET',
                url: "{$this->_domain_api}/movie/top_rated",
                options: $this->_options
            );

            return $r->toArray();
        }

        /**
         * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
         */
        public function getUpcoming(): array {
            $r = $this->client->request(
                method: 'GET',
                url: "{$this->_domain_api}/movie/upcoming",
                options: $this->_options
            );

            return $r->toArray();
        }

        /**
         * @param string|null $size
         * @param string $endpoint
         *
         * @return mixed
         * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
         * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
         */
        public function tmdb_image(?string $size, string $endpoint): mixed {
            $url = $this->_domain_img.($size != "" ? $size : 'original')."/".$endpoint;

            return $this->client->request(method: 'GET', url: $url)->getContent();
        }
    }
}