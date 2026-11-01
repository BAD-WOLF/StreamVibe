<?php

declare(strict_types = 1);

namespace App\Application\Person\DTO\TvCredits\Response\Model;

/**
 * Uma série em que a pessoa atuou, em `data.cast` de
 * GET /person/{personId}/tv/credits: dados da série + o papel.
 *
 * NOTA (out/2026): criado com o endpoint de créditos de TV, que antes
 * existia só como placeholder (sempre null) no GetPersonDetailsUseCase.
 * Espelha os itens de créditos de filme (MovieCastCredit/MovieCrewCredit)
 * com os campos de série do TMDB: name/original_name no lugar de
 * title/original_title, first_air_date/first_air_year no lugar de
 * release_date/release_year, e episode_count/origin_country.
 */
final class TvCastCredit {
    /**
     * @param int          $id                id da série no TMDB
     * @param string       $name
     * @param string       $original_name
     * @param string       $character
     * @param string       $credit_id
     * @param int          $episode_count     episódios em que a pessoa aparece/trabalhou
     * @param string|null  $first_air_date    "YYYY-MM-DD" (o TMDB pode mandar vazio)
     * @param string|null  $poster_path
     * @param string|null  $backdrop_path
     * @param float        $vote_average      0 a 10
     * @param int          $vote_count
     * @param float        $popularity
     * @param bool         $adult
     * @param list<int>    $genre_ids
     * @param list<string> $origin_country    códigos ISO 3166-1
     * @param string       $original_language
     * @param string       $overview
     * @param string|null  $poster_url        pôster em w342
     * @param string|null  $backdrop_url      backdrop em w780
     * @param int|null     $first_air_year
     * @param int          $rating_percentage vote_average em 0 a 100
     */
    public function __construct(
        public private(set) int $id,
        public private(set) string $name,
        public private(set) string $original_name,
        public private(set) string $character,
        public private(set) string $credit_id,
        public private(set) int $episode_count,
        public private(set) ?string $first_air_date,
        public private(set) ?string $poster_path,
        public private(set) ?string $backdrop_path,
        public private(set) float $vote_average,
        public private(set) int $vote_count,
        public private(set) float $popularity,
        public private(set) bool $adult,
        public private(set) array $genre_ids,
        public private(set) array $origin_country,
        public private(set) string $original_language,
        public private(set) string $overview,
        public private(set) ?string $poster_url,
        public private(set) ?string $backdrop_url,
        public private(set) ?int $first_air_year,
        public private(set) int $rating_percentage,
    ) {
    }
}
