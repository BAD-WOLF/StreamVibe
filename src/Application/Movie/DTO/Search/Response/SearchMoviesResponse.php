<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Search\Response;

use App\Application\Movie\DTO\Shared\Response\PaginatedMovieListResponse;

/**
 * Resposta do SearchMoviesUseCase: `data` da busca de filmes.
 *
 * NOTA (out/2026): resolvida a "mudança maior, separada" anotada aqui em
 * ago/2026. O OutputMapperRegistry agora chega aos controllers e o JSON
 * real sai do SearchMoviesOutput (o mesmo modelo que o Swagger documenta),
 * não mais do toArray(). O conteúdo de `data` continua o que o
 * MovieSearchApiTest fixa — movies, current_page, total_pages,
 * total_results e os campos de navegação (has_next, next_page...) —, agora
 * vindo das propriedades públicas herdadas de PaginatedMovieListResponse.
 * O envelope perdeu o `errors: []`, que vinha sempre vazio em resposta de
 * sucesso (erro sai no formato do ExceptionListener).
 */
final class SearchMoviesResponse extends PaginatedMovieListResponse {
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
