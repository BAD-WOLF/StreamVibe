<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Credits\Response\Model;

/**
 * Uma pessoa do elenco em `data.cast` (GET /movies/{movieId}/credits).
 *
 * NOTA (out/2026):
 * - Propriedades passaram a `public private(set)` (antes eram públicas
 *   com escrita livre) e o arquivo ganhou declare(strict_types=1).
 * - O #[NotExposed] e os #[ApiProperty] vazios saíram. A NOTA antiga
 *   dizia que o #[NotExposed] era necessário para o Swagger documentar a
 *   classe, mas o API Platform só lê atributos das classes em
 *   api_platform.mapping.paths (src/Infrastructure/ApiPlatform) — aqui
 *   eles não tinham efeito nenhum, e prendiam o Application ao API
 *   Platform. O schema de cast/crew sai do tipo dos itens (@param
 *   list<CastMember> no GetMovieCreditsResponse) e das propriedades
 *   tipadas desta classe.
 * - Propriedades promovidas no construtor, como nos outros Response/Model
 *   (MovieListItem, Genre...).
 */
final class CastMember {
    /**
     * @param bool        $adult
     * @param int         $gender               0 não informado, 1 feminino, 2 masculino, 3 não binário
     * @param int         $id                   id da pessoa no TMDB
     * @param string      $known_for_department
     * @param string      $name
     * @param string      $original_name
     * @param float       $popularity
     * @param string|null $profile_path
     * @param int         $cast_id
     * @param string      $character
     * @param string      $credit_id
     * @param int         $order                posição nos créditos (0 = protagonista)
     */
    public function __construct(
        public private(set) bool $adult,
        public private(set) int $gender,
        public private(set) int $id,
        public private(set) string $known_for_department,
        public private(set) string $name,
        public private(set) string $original_name,
        public private(set) float $popularity,
        public private(set) ?string $profile_path,
        public private(set) int $cast_id,
        public private(set) string $character,
        public private(set) string $credit_id,
        public private(set) int $order,
    ) {
    }
}
