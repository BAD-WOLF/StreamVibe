<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Shared\Response\Model;

/**
 * Um filme em `data.movies` nas listas de filmes (busca, tendência,
 * populares, mais bem avaliados, em cartaz, similares e recomendações).
 *
 * Campos do TMDB + os derivados que o MovieListItemMapper calcula:
 * poster_url (w500), backdrop_url (w1280), thumbnail_url (w342),
 * rating_percentage, release_year e formatted_overview (sinopse cortada
 * em 250 caracteres).
 *
 * NOTA (out/2026): antes o item era um array montado pelo mapper. O JSON
 * continua o mesmo (mesmas chaves, mesma ordem), mas agora o item tem
 * tipo: o Swagger documenta campo a campo em vez de "array", e quem monta
 * o item não tem como esquecer ou renomear um campo. Os nomes das
 * propriedades são os do JSON (snake_case), como em CastMember/CrewMember.
 */
final class MovieListItem {
    /**
     * @param int         $id
     * @param string      $title
     * @param string      $original_title
     * @param string      $overview
     * @param string|null $release_date       "YYYY-MM-DD" (o TMDB pode mandar vazio)
     * @param string|null $poster_path
     * @param string|null $backdrop_path
     * @param float       $vote_average       0 a 10
     * @param int         $vote_count
     * @param float       $popularity
     * @param list<int>   $genre_ids
     * @param bool        $adult
     * @param bool        $video
     * @param string      $original_language
     * @param string|null $poster_url
     * @param string|null $backdrop_url
     * @param string|null $thumbnail_url
     * @param int         $rating_percentage  vote_average em 0 a 100
     * @param int|null    $release_year
     * @param string|null $formatted_overview
     */
    public function __construct(
        public private(set) int $id,
        public private(set) string $title,
        public private(set) string $original_title,
        public private(set) string $overview,
        public private(set) ?string $release_date,
        public private(set) ?string $poster_path,
        public private(set) ?string $backdrop_path,
        public private(set) float $vote_average,
        public private(set) int $vote_count,
        public private(set) float $popularity,
        public private(set) array $genre_ids,
        public private(set) bool $adult,
        public private(set) bool $video,
        public private(set) string $original_language,
        public private(set) ?string $poster_url,
        public private(set) ?string $backdrop_url,
        public private(set) ?string $thumbnail_url,
        public private(set) int $rating_percentage,
        public private(set) ?int $release_year,
        public private(set) ?string $formatted_overview,
    ) {
    }
}
