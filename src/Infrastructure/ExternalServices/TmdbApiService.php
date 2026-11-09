<?php

declare(strict_types = 1);

namespace App\Infrastructure\ExternalServices;

use App\Application\Shared\Gateway\MovieDatabaseGatewayInterface;
use App\Application\System\DTO\Dependencies\Response\Model\ExternalServiceStatus;
use App\Application\System\Port\MovieDatabaseProbeInterface;
use App\Domain\Exception\BusinessLogicException;
use App\Domain\Exception\DomainException;
use App\Domain\Exception\ExternalServiceException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * Adaptador HTTP do TMDB (The Movie Database).
 *
 * NOTA (out/2026): passou a implementar a porta
 * App\Application\Shared\Gateway\MovieDatabaseGatewayInterface, e é o único
 * lugar do projeto que conhece o HttpClient. Toda chamada passa por
 * requestJson()/requestBinary(), que traduzem as exceções do HttpClient
 * para exceções de domínio (tabela na própria interface). Com isso o
 * try/catch repetido nos UseCases saiu, e "filme inexistente" passou a
 * responder 404 em vez de 400 (antes todo 4xx do TMDB virava
 * ExternalServiceException::clientError, código 400).
 *
 * Outras mudanças:
 * - $language (opcional) é repassado ao TMDB em todos os métodos de
 *   conteúdo; antes os DTOs aceitavam `language` e ninguém o enviava.
 * - searchMovies() envia region, year e primary_release_year, que o
 *   SearchMoviesRequest já aceitava e eram ignorados.
 * - `page` sempre vai na query (antes só quando > 1).
 * - Similar/Recommendations ganharam `page` (antes era sempre a página 1).
 * - getUpcoming(), getMovieGenres() e discoverMovies() continuam aqui (não
 *   têm endpoint ainda), agora pelo mesmo caminho de tradução de erros.
 *
 * NOTA (nov/2026): passou a implementar também a
 * App\Application\System\Port\MovieDatabaseProbeInterface (probe(), a
 * conferência do TMDB no painel do sistema) — assim o HttpClient e o
 * endereço do TMDB continuam só aqui.
 */
class TmdbApiService implements MovieDatabaseGatewayInterface, MovieDatabaseProbeInterface {
    private const string API_BASE_URL   = 'https://api.themoviedb.org/3';
    private const string IMAGE_BASE_URL = 'https://image.tmdb.org/t/p/';
    private const string SERVICE_NAME   = 'TMDB';

    /**
     * Limite (segundos) da conferência do probe(): o painel do sistema não
     * fica preso esperando o TMDB.
     */
    private const float PROBE_TIMEOUT = 5.0;

    private array $options;

    /**
     * @param \Symfony\Contracts\HttpClient\HttpClientInterface $client
     * @param \Psr\Log\LoggerInterface                          $logger
     * @param string                                            $tmdbToken
     */
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
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
     * {@inheritDoc}
     */
    public function searchMovies(
        string $query,
        int $page = 1,
        bool $includeAdult = false,
        ?string $language = null,
        ?string $region = null,
        ?int $year = null,
        ?int $primaryReleaseYear = null,
    ): array {
        return $this->requestJson('/search/movie', [
            'query' => $query,
            'page' => $page,
            'include_adult' => $includeAdult ? 'true' : 'false',
            'language' => $language,
            'region' => $region,
            'year' => $year,
            'primary_release_year' => $primaryReleaseYear,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getTrendingMovies(
        string $timeWindow,
        int $page = 1,
        ?string $language = null,
    ): array {
        return $this->requestJson("/trending/movie/{$timeWindow}", [
            'page' => $page,
            'language' => $language,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getPopularMovies(int $page = 1, ?string $language = null): array {
        return $this->requestJson('/movie/popular', [
            'page' => $page,
            'language' => $language,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getTopRatedMovies(int $page = 1, ?string $language = null): array {
        return $this->requestJson('/movie/top_rated', [
            'page' => $page,
            'language' => $language,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getNowPlayingMovies(int $page = 1, ?string $language = null): array {
        return $this->requestJson('/movie/now_playing', [
            'page' => $page,
            'language' => $language,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getMovieDetails(int $movieId, ?string $language = null): array {
        return $this->requestJson(
            "/movie/{$movieId}",
            ['language' => $language],
            'Movie',
            $movieId,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getMovieCredits(int $movieId, ?string $language = null): array {
        return $this->requestJson(
            "/movie/{$movieId}/credits",
            ['language' => $language],
            'Movie',
            $movieId,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getMovieImages(int $movieId, ?string $language = null): array {
        return $this->requestJson(
            "/movie/{$movieId}/images",
            ['language' => $language],
            'Movie',
            $movieId,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getMovieVideos(int $movieId, ?string $language = null): array {
        return $this->requestJson(
            "/movie/{$movieId}/videos",
            ['language' => $language],
            'Movie',
            $movieId,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getSimilarMovies(
        int $movieId,
        int $page = 1,
        ?string $language = null,
    ): array {
        return $this->requestJson(
            "/movie/{$movieId}/similar",
            ['page' => $page, 'language' => $language],
            'Movie',
            $movieId,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getMovieRecommendations(
        int $movieId,
        int $page = 1,
        ?string $language = null,
    ): array {
        return $this->requestJson(
            "/movie/{$movieId}/recommendations",
            ['page' => $page, 'language' => $language],
            'Movie',
            $movieId,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getPersonDetails(int $personId, ?string $language = null): array {
        return $this->requestJson(
            "/person/{$personId}",
            ['language' => $language],
            'Person',
            $personId,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getPersonMovieCredits(int $personId, ?string $language = null): array {
        return $this->requestJson(
            "/person/{$personId}/movie_credits",
            ['language' => $language],
            'Person',
            $personId,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getPersonTvCredits(int $personId, ?string $language = null): array {
        return $this->requestJson(
            "/person/{$personId}/tv_credits",
            ['language' => $language],
            'Person',
            $personId,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getPersonImages(int $personId): array {
        return $this->requestJson(
            "/person/{$personId}/images",
            [],
            'Person',
            $personId,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getPersonExternalIds(int $personId): array {
        return $this->requestJson(
            "/person/{$personId}/external_ids",
            [],
            'Person',
            $personId,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getImage(string $size, string $path): string {
        $url = self::IMAGE_BASE_URL.$size.'/'.ltrim($path, '/');

        try {
            return $this->client->request('GET', $url)->getContent();
        } catch (HttpClientExceptionInterface $e) {
            throw $this->translate($e, $url, 'Image', $path);
        }
    }

    /**
     * Get upcoming movies
     *
     * @return array<string, mixed>
     */
    public function getUpcoming(int $page = 1, ?string $language = null): array {
        return $this->requestJson('/movie/upcoming', [
            'page' => $page,
            'language' => $language,
        ]);
    }

    /**
     * Get movie genres
     *
     * @return array<string, mixed>
     */
    public function getMovieGenres(?string $language = null): array {
        return $this->requestJson('/genre/movie/list', ['language' => $language]);
    }

    /**
     * Discover movies with filters
     *
     * @param array<string, scalar|null> $filters
     *
     * @return array<string, mixed>
     */
    public function discoverMovies(array $filters = []): array {
        return $this->requestJson('/discover/movie', $filters);
    }

    /**
     * {@inheritDoc}
     *
     * GET /authentication é o endpoint do TMDB que só confere o token (200
     * = aceito, 401 = recusado). Sem resposta em PROBE_TIMEOUT segundos, o
     * TMDB conta como fora do ar.
     *
     * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
     */
    public function probe(): ExternalServiceStatus {
        $started = hrtime(true);

        try {
            $status = $this->client->request('GET', self::API_BASE_URL.'/authentication', $this->options + [
                'timeout' => self::PROBE_TIMEOUT,
                'max_duration' => self::PROBE_TIMEOUT,
            ])->getStatusCode();
        } catch (TransportExceptionInterface $exception) {
            return new ExternalServiceStatus(
                reachable: false,
                latencyMs: null,
                statusCode: null,
                error: $exception->getMessage(),
            );
        }

        return new ExternalServiceStatus(
            reachable: true,
            latencyMs: round((hrtime(true) - $started) / 1_000_000, 2),
            statusCode: $status,
            error: match (true) {
                $status >= 200 && $status < 300 => null,
                $status === 401, $status === 403 => 'Authentication rejected by TMDB (check TMDB_TOKEN)',
                $status === 429 => 'Rate limited by TMDB',
                default => sprintf('TMDB answered with HTTP %d', $status),
            },
        );
    }

    /**
     * GET na API do TMDB e decodificação do JSON. Parâmetros null não vão
     * para a query.
     *
     * @param string                     $path       caminho a partir de /3 (ex.: "/movie/603")
     * @param array<string, scalar|null> $query
     * @param string|null                $resource   nome do recurso para o 404 (ex.: "Movie")
     * @param int|string|null            $identifier id do recurso para o 404
     *
     * @return array<string, mixed>
     */
    private function requestJson(
        string $path,
        array $query = [],
        ?string $resource = null,
        int|string|null $identifier = null,
    ): array {
        $query = array_filter(
            $query,
            static fn(mixed $value): bool => $value !== null,
        );
        $url = self::API_BASE_URL.$path.($query === [] ? '' : '?'.http_build_query($query));

        try {
            return $this->client->request('GET', $url, $this->options)->toArray();
        } catch (HttpClientExceptionInterface $e) {
            throw $this->translate($e, $path, $resource, $identifier);
        }
    }

    /**
     * Converte a exceção do HttpClient na exceção de domínio
     * correspondente (ver tabela em MovieDatabaseGatewayInterface).
     *
     * @param \Symfony\Contracts\HttpClient\Exception\ExceptionInterface $exception
     * @param string          $target     path/URL chamado (vai para o log e o contexto)
     * @param string|null     $resource
     * @param int|string|null $identifier
     *
     * @return \App\Domain\Exception\DomainException
     */
    private function translate(
        HttpClientExceptionInterface $exception,
        string $target,
        ?string $resource,
        int|string|null $identifier,
    ): DomainException {
        $context = ['target' => $target];

        if ($exception instanceof ClientExceptionInterface) {
            $status = $exception->getResponse()->getStatusCode();

            $this->logger->warning('TMDB client error', $context + [
                'status' => $status,
                'error' => $exception->getMessage(),
            ]);

            return match (true) {
                $status === 404 && $resource !== null => BusinessLogicException::notFound($resource, $identifier),
                $status === 401, $status === 403 => ExternalServiceException::serverError(
                    self::SERVICE_NAME,
                    'Authentication rejected by TMDB (check TMDB_TOKEN)',
                    $context,
                ),
                $status === 429 => ExternalServiceException::rateLimited(
                    self::SERVICE_NAME,
                    $this->retryAfter($exception),
                    $context,
                ),
                default => ExternalServiceException::clientError(
                    self::SERVICE_NAME,
                    $exception->getMessage(),
                    $context,
                ),
            };
        }

        $this->logger->error('TMDB request failed', $context + [
            'error' => $exception->getMessage(),
            'error_class' => $exception::class,
        ]);

        return match (true) {
            $exception instanceof ServerExceptionInterface,
            $exception instanceof RedirectionExceptionInterface => ExternalServiceException::serverError(
                self::SERVICE_NAME,
                $exception->getMessage(),
                $context,
            ),
            $exception instanceof DecodingExceptionInterface => ExternalServiceException::invalidResponse(
                self::SERVICE_NAME,
                $exception->getMessage(),
                $context,
            ),
            $exception instanceof TransportExceptionInterface => ExternalServiceException::connectionFailed(
                self::SERVICE_NAME,
                $exception->getMessage(),
                $context,
            ),
            default => ExternalServiceException::serverError(
                self::SERVICE_NAME,
                $exception->getMessage(),
                $context,
            ),
        };
    }

    /**
     * @param \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface $exception
     *
     * @return int|null
     */
    private function retryAfter(ClientExceptionInterface $exception): ?int {
        try {
            $value = $exception->getResponse()->getHeaders(false)['retry-after'][0] ?? null;
        } catch (Throwable) {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}
