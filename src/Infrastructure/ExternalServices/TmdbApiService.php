<?php

declare(strict_types = 1);

namespace App\Infrastructure\ExternalServices;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 *
 */
class TmdbApiService {
    private const string API_BASE_URL   = 'https://api.themoviedb.org/3';
    private const string IMAGE_BASE_URL = 'https://image.tmdb.org/t/p/';

    private array $options;

    /**
     * @param \Symfony\Contracts\HttpClient\HttpClientInterface $client
     * @param string                                            $tmdbToken
     */
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $tmdbToken,
    ) {
        $this->options = [
            'headers' => [
                'Authorization' => "Bearer {$this->tmdbToken}",
                'accept' => 'application/json',
            ],
        ];
    }

    /**
     * Search for movies based on a query string
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function searchMovies(
        string $query,
        int $page = 1,
        bool $includeAdult = true,
    ): array {
        $url = self::API_BASE_URL.'/search/movie';
        $queryParams = [
            'query' => $query,
            'page' => $page,
            'include_adult' => $includeAdult ? 'true' : 'false',
        ];

        $response = $this->client->request(
            'GET',
            $url.'?'.http_build_query($queryParams),
            $this->options,
        );

        return $response->toArray();
    }

    /**
     * Get detailed information about a specific movie
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getMovieDetails(int $movieId): array {
        $url = self::API_BASE_URL."/movie/{$movieId}";

        $response = $this->client->request('GET', $url, $this->options);

        return $response->toArray();
    }

    /**
     * Get movie credits (cast and crew)
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getMovieCredits(int $movieId): array {
        $url = self::API_BASE_URL."/movie/{$movieId}/credits";

        $response = $this->client->request('GET', $url, $this->options);

        return $response->toArray();
    }

    /**
     * Get detailed information about a person
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getPersonDetails(int $personId): array {
        $url = self::API_BASE_URL."/person/{$personId}";

        $response = $this->client->request('GET', $url, $this->options);

        return $response->toArray();
    }

    /**
     * Get movies for a specific person
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getPersonMovieCredits(int $personId): array {
        $url = self::API_BASE_URL."/person/{$personId}/movie_credits";

        $response = $this->client->request('GET', $url, $this->options);

        return $response->toArray();
    }

    /**
     * Get currently playing movies
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getNowPlaying(int $page = 1): array {
        $url = self::API_BASE_URL.'/movie/now_playing';

        if ($page > 1) {
            $url .= "?page={$page}";
        }

        $response = $this->client->request('GET', $url, $this->options);

        return $response->toArray();
    }

    /**
     * Get popular movies
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getPopular(int $page = 1): array {
        $url = self::API_BASE_URL.'/movie/popular';

        if ($page > 1) {
            $url .= "?page={$page}";
        }

        $response = $this->client->request('GET', $url, $this->options);

        return $response->toArray();
    }

    /**
     * Get top rated movies
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getTopRated(int $page = 1): array {
        $url = self::API_BASE_URL.'/movie/top_rated';

        if ($page > 1) {
            $url .= "?page={$page}";
        }

        $response = $this->client->request('GET', $url, $this->options);

        return $response->toArray();
    }

    /**
     * Get upcoming movies
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getUpcoming(int $page = 1): array {
        $url = self::API_BASE_URL.'/movie/upcoming';

        if ($page > 1) {
            $url .= "?page={$page}";
        }

        $response = $this->client->request('GET', $url, $this->options);

        return $response->toArray();
    }

    /**
     * Get TMDB image by size and endpoint
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getImage(?string $size, string $endpoint): string {
        $size = $size ?: 'original';
        $url = self::IMAGE_BASE_URL."{$size}/{$endpoint}";

        $response = $this->client->request('GET', $url);

        return $response->getContent();
    }

    /**
     * Get movie genres
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getMovieGenres(): array {
        $url = self::API_BASE_URL.'/genre/movie/list';

        $response = $this->client->request('GET', $url, $this->options);

        return $response->toArray();
    }

    /**
     * Discover movies with filters
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function discoverMovies(array $filters = []): array {
        $url = self::API_BASE_URL.'/discover/movie';

        if (!empty($filters)) {
            $url .= '?'.http_build_query($filters);
        }

        $response = $this->client->request('GET', $url, $this->options);

        return $response->toArray();
    }

    /**
     * Get movie recommendations
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getMovieRecommendations(int $movieId, int $page = 1): array {
        $url = self::API_BASE_URL."/movie/{$movieId}/recommendations";

        if ($page > 1) {
            $url .= "?page={$page}";
        }

        $response = $this->client->request('GET', $url, $this->options);

        return $response->toArray();
    }

    /**
     * Get similar movies
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getSimilarMovies(int $movieId, int $page = 1): array {
        $url = self::API_BASE_URL."/movie/{$movieId}/similar";

        if ($page > 1) {
            $url .= "?page={$page}";
        }

        $response = $this->client->request('GET', $url, $this->options);

        return $response->toArray();
    }

    /**
     * Get movie videos (trailers, teasers, etc.)
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getMovieVideos(int $movieId): array {
        $url = self::API_BASE_URL."/movie/{$movieId}/videos";

        $response = $this->client->request('GET', $url, $this->options);

        return $response->toArray();
    }

    /**
     * Get movie images (posters, backdrops)
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getMovieImages(int $movieId): array {
        $url = self::API_BASE_URL."/movie/{$movieId}/images";

        $response = $this->client->request('GET', $url, $this->options);

        return $response->toArray();
    }

    /**
     * Get trending movies
     *
     * @param string $timeWindow 'day' or 'week'
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getTrending(
        string $timeWindow = 'week',
        int $page = 1,
    ): array {
        $url = self::API_BASE_URL."/trending/movie/{$timeWindow}";

        if ($page > 1) {
            $url .= "?page={$page}";
        }

        $response = $this->client->request('GET', $url, $this->options);

        return $response->toArray();
    }
}
