<?php

namespace App\Service {

    use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
    use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
    use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
    use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
    use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
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
            $r = $this->client->request('GET', "{$this->_domain}/search/movie?query=$search_query&include_adult=true&language=en-US&page=$page", $this->_options);
            return $r->toArray();
        }

        /**
         * @param int $movie_id
         * @return array
         * @throws ClientExceptionInterface
         * @throws DecodingExceptionInterface
         * @throws RedirectionExceptionInterface
         * @throws ServerExceptionInterface
         * @throws TransportExceptionInterface
         */
        public function getMoviePersons(int $movie_id): array
        {
            $r = $this->client->request('GET', "{$this->_domain}/movie/$movie_id/credits?language=en-US", $this->_options);
            return $r->toArray();
        }

        /**
         * @param int $persson_id
         * @return array
         * @throws ClientExceptionInterface
         * @throws DecodingExceptionInterface
         * @throws RedirectionExceptionInterface
         * @throws ServerExceptionInterface
         * @throws TransportExceptionInterface
         */
        public function getPersonMovies(int $persson_id): array
        {
            $r = $this->client->request('GET', "{$this->_domain}/person/$persson_id/movie_credits?language=en-US", $this->_options);
            return $r->toArray();
        }
    }
}