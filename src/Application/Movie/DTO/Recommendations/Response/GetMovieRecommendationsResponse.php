<?php

declare(strict_types=1);

namespace App\Application\Movie\DTO\Recommendations\Response;

use App\Application\Movie\DTO\Shared\Response\PaginatedMovieListResponse;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Response DTO for recomendações de filme
 *
 * It is automatically mapped to the GetMovieRecommendationsOutput ApiPlatform Output
 * Model via the auto-mapping system.
 *
 * NOTA (out/2026): passou a estender PaginatedMovieListResponse, o mesmo
 * formato de lista da busca. Antes `data` trazia a lista duas vezes
 * (recommendations + results) e a paginação aninhada em `pagination`; agora é
 * movie_id + movies + paginação achatada (current_page, total_pages,
 * total_results, has_next...). Saíram success/errors (envelope nunca
 * preenchido aqui), error(), os getters de compatibilidade e o toArray().
 */
final class GetMovieRecommendationsResponse extends PaginatedMovieListResponse
{
    /**
     * @param int                                                                  $movieId
     * @param list<\App\Application\Movie\DTO\Shared\Response\Model\MovieListItem> $movies
     * @param int                                                                  $page
     * @param int                                                                  $totalPages
     * @param int                                                                  $totalResults
     * @param string|null                                                          $message
     */
    public function __construct(
        #[SerializedName('movie_id')]
        public private(set) int $movieId {
            /**
             * @return int
             */ get => $this->movieId;
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
     * Create a successful response
     *
     * @param int                                                                  $movieId
     * @param list<\App\Application\Movie\DTO\Shared\Response\Model\MovieListItem> $movies
     * @param int                                                                  $page
     * @param int                                                                  $totalPages
     * @param int                                                                  $totalResults
     * @param string|null                                                          $message
     *
     * @return self
     */
    public static function success(
        int $movieId,
        array $movies,
        int $page,
        int $totalPages,
        int $totalResults,
        ?string $message = null,
    ): self {
        return new self(
            movieId: $movieId,
            movies: $movies,
            page: $page,
            totalPages: $totalPages,
            totalResults: $totalResults,
            message: $message,
        );
    }
}
