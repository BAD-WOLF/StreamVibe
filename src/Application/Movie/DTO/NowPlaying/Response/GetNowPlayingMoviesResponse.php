<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\NowPlaying\Response;

use App\Application\Movie\DTO\Shared\Response\PaginatedMovieListResponse;

/**
 * Resposta do GetNowPlayingMoviesUseCase: `data` dos filmes em cartaz.
 *
 * NOTA (out/2026): antes reaproveitava o SearchMoviesResponse. Ganhou DTO
 * próprio (e Output próprio) para fechar o espelhamento por endpoint; o
 * conteúdo de `data` é o mesmo formato de lista paginada da busca.
 */
final class GetNowPlayingMoviesResponse extends PaginatedMovieListResponse {
    /**
     * @param list<\App\Application\Movie\DTO\Shared\Response\Model\MovieListItem> $movies
     * @param int                                                                  $page
     * @param int                                                                  $totalPages
     * @param int                                                                  $totalResults
     * @param string|null                                                          $message
     *
     * @return self
     */
    public static function success(
        array $movies,
        int $page,
        int $totalPages,
        int $totalResults,
        ?string $message = null,
    ): self {
        return new self(
            movies: $movies,
            page: $page,
            totalPages: $totalPages,
            totalResults: $totalResults,
            message: $message,
        );
    }
}
