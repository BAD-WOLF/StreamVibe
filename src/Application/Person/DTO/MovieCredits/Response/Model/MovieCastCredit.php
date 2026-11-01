<?php

declare(strict_types = 1);

namespace App\Application\Person\DTO\MovieCredits\Response\Model;

/**
 * Um filme em que a pessoa atuou, em `data.cast` de
 * GET /person/{personId}/movie/credits: dados do filme + o papel.
 *
 * NOTA (out/2026): antes cada item era um array montado em
 * GetPersonMovieCreditsUseCase::processMovieCredits(). O JSON continua com
 * as mesmas chaves e a mesma ordem; a classe dá tipo ao item e o documenta
 * no Swagger (antes `cast`/`crew` apareciam como "array").
 */
final class MovieCastCredit {
    /**
     * @param int         $id                id do filme no TMDB
     * @param string      $title
     * @param string      $original_title
     * @param string      $character
     * @param string      $credit_id
     * @param int         $order             posição nos créditos do filme
     * @param string|null $release_date      "YYYY-MM-DD" (o TMDB pode mandar vazio)
     * @param string|null $poster_path
     * @param string|null $backdrop_path
     * @param float       $vote_average      0 a 10
     * @param int         $vote_count
     * @param float       $popularity
     * @param bool        $adult
     * @param list<int>   $genre_ids
     * @param string      $original_language
     * @param string      $overview
     * @param bool        $video
     * @param string|null $poster_url        pôster em w342
     * @param string|null $backdrop_url      backdrop em w780
     * @param int|null    $release_year
     * @param int         $rating_percentage vote_average em 0 a 100
     */
    public function __construct(
        public private(set) int $id,
        public private(set) string $title,
        public private(set) string $original_title,
        public private(set) string $character,
        public private(set) string $credit_id,
        public private(set) int $order,
        public private(set) ?string $release_date,
        public private(set) ?string $poster_path,
        public private(set) ?string $backdrop_path,
        public private(set) float $vote_average,
        public private(set) int $vote_count,
        public private(set) float $popularity,
        public private(set) bool $adult,
        public private(set) array $genre_ids,
        public private(set) string $original_language,
        public private(set) string $overview,
        public private(set) bool $video,
        public private(set) ?string $poster_url,
        public private(set) ?string $backdrop_url,
        public private(set) ?int $release_year,
        public private(set) int $rating_percentage,
    ) {
    }
}
