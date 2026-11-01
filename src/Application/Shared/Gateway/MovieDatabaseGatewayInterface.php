<?php

declare(strict_types = 1);

namespace App\Application\Shared\Gateway;

/**
 * Porta da camada Application para a base de filmes externa (TMDB — The
 * Movie Database). A implementação fica na Infrastructure
 * (App\Infrastructure\ExternalServices\TmdbApiService).
 *
 * NOTA (out/2026): antes cada UseCase dependia do TmdbApiService concreto
 * e das exceções do HttpClient (ClientExceptionInterface,
 * ServerExceptionInterface...), com o mesmo try/catch de ~40 linhas
 * repetido em 13 UseCases. Agora a dependência aponta para dentro (esta
 * interface) e quem conhece HTTP é só o adaptador, que traduz as falhas
 * para exceções de domínio:
 *
 * - 404 do TMDB                     -> BusinessLogicException::notFound() (404)
 * - outros 4xx (ex.: página > 500)  -> ExternalServiceException::clientError() (400)
 * - token recusado (401/403)        -> ExternalServiceException::serverError() (502)
 * - 429 (limite do TMDB)            -> ExternalServiceException::rateLimited() (503)
 * - 5xx/redirecionamento            -> ExternalServiceException::serverError() (502)
 * - JSON inválido                   -> ExternalServiceException::invalidResponse() (502)
 * - rede/DNS/timeout                -> ExternalServiceException::connectionFailed() (503)
 *
 * Os retornos são os arrays no formato do próprio TMDB; cada UseCase
 * transforma no DTO de resposta dele. $language é o código opcional do
 * TMDB (ex.: "pt-BR"); null deixa o TMDB usar o padrão dele (en-US).
 *
 * @throws \App\Domain\Exception\BusinessLogicException  em todos os métodos (recurso inexistente)
 * @throws \App\Domain\Exception\ExternalServiceException em todos os métodos (falha do TMDB)
 */
interface MovieDatabaseGatewayInterface {
    /**
     * @return array<string, mixed>
     */
    public function searchMovies(
        string $query,
        int $page = 1,
        bool $includeAdult = false,
        ?string $language = null,
        ?string $region = null,
        ?int $year = null,
        ?int $primaryReleaseYear = null,
    ): array;

    /**
     * @param string $timeWindow "day" ou "week"
     *
     * @return array<string, mixed>
     */
    public function getTrendingMovies(
        string $timeWindow,
        int $page = 1,
        ?string $language = null,
    ): array;

    /**
     * @return array<string, mixed>
     */
    public function getPopularMovies(int $page = 1, ?string $language = null): array;

    /**
     * @return array<string, mixed>
     */
    public function getTopRatedMovies(int $page = 1, ?string $language = null): array;

    /**
     * @return array<string, mixed>
     */
    public function getNowPlayingMovies(int $page = 1, ?string $language = null): array;

    /**
     * @return array<string, mixed>
     */
    public function getMovieDetails(int $movieId, ?string $language = null): array;

    /**
     * @return array<string, mixed>
     */
    public function getMovieCredits(int $movieId, ?string $language = null): array;

    /**
     * @return array<string, mixed>
     */
    public function getMovieImages(int $movieId, ?string $language = null): array;

    /**
     * @return array<string, mixed>
     */
    public function getMovieVideos(int $movieId, ?string $language = null): array;

    /**
     * @return array<string, mixed>
     */
    public function getSimilarMovies(
        int $movieId,
        int $page = 1,
        ?string $language = null,
    ): array;

    /**
     * @return array<string, mixed>
     */
    public function getMovieRecommendations(
        int $movieId,
        int $page = 1,
        ?string $language = null,
    ): array;

    /**
     * @return array<string, mixed>
     */
    public function getPersonDetails(int $personId, ?string $language = null): array;

    /**
     * @return array<string, mixed>
     */
    public function getPersonMovieCredits(int $personId, ?string $language = null): array;

    /**
     * @return array<string, mixed>
     */
    public function getPersonTvCredits(int $personId, ?string $language = null): array;

    /**
     * @return array<string, mixed>
     */
    public function getPersonImages(int $personId): array;

    /**
     * @return array<string, mixed>
     */
    public function getPersonExternalIds(int $personId): array;

    /**
     * Baixa uma imagem da CDN do TMDB (image.tmdb.org).
     *
     * @param string $size tamanho do TMDB (ex.: "w500", "original")
     * @param string $path caminho do arquivo, começando com "/"
     *
     * @return string conteúdo binário da imagem
     */
    public function getImage(string $size, string $path): string;
}
