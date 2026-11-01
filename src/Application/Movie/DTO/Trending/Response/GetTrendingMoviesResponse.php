<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Trending\Response;

use App\Application\Movie\DTO\Shared\Response\PaginatedMovieListResponse;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do GetTrendingMoviesUseCase: `data` dos filmes em tendência.
 *
 * NOTA (out/2026): antes reaproveitava o SearchMoviesResponse. Ganhou DTO
 * próprio (e Output próprio) para fechar o espelhamento por endpoint, e
 * devolve `time_window`: assim o cliente — e o teste — conseguem ver qual
 * janela foi usada (o bug do {timeWindow} ignorado era invisível na
 * resposta).
 */
final class GetTrendingMoviesResponse extends PaginatedMovieListResponse {
    /**
     * @param string                                                               $timeWindow
     * @param list<\App\Application\Movie\DTO\Shared\Response\Model\MovieListItem> $movies
     * @param int                                                                  $page
     * @param int                                                                  $totalPages
     * @param int                                                                  $totalResults
     * @param string|null                                                          $message
     */
    public function __construct(
        #[SerializedName('time_window')]
        public private(set) string $timeWindow {
            /**
             * @return string
             */ get => $this->timeWindow;
        },
        array $movies,
        int $page,
        int $totalPages,
        int $totalResults,
        ?string $message = null,
    ) {
        parent::__construct(
            movies: $movies,
            page: $page,
            totalPages: $totalPages,
            totalResults: $totalResults,
            message: $message,
        );
    }

    /**
     * @param string                                                               $timeWindow
     * @param list<\App\Application\Movie\DTO\Shared\Response\Model\MovieListItem> $movies
     * @param int                                                                  $page
     * @param int                                                                  $totalPages
     * @param int                                                                  $totalResults
     * @param string|null                                                          $message
     *
     * @return self
     */
    public static function success(
        string $timeWindow,
        array $movies,
        int $page,
        int $totalPages,
        int $totalResults,
        ?string $message = null,
    ): self {
        return new self(
            timeWindow: $timeWindow,
            movies: $movies,
            page: $page,
            totalPages: $totalPages,
            totalResults: $totalResults,
            message: $message,
        );
    }
}
