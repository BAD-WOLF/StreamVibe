<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Details\Response\Model;

/**
 * Uma produtora em `data.production_companies` (GET /movies/{movieId}).
 *
 * NOTA (out/2026): a classe existia mas nunca era instanciada (o campo
 * saía como o array cru do TMDB). Agora o GetMovieDetailsUseCase monta
 * cada item; o JSON é o mesmo. Propriedades em `public private(set)` no
 * construtor, como os outros Response/Model; o #[ApiProperty] vazio saiu
 * (não tinha efeito e prendia o Application ao API Platform).
 */
final class ProductionCompany {
    /**
     * @param int         $id
     * @param string|null $logo_path
     * @param string      $name
     * @param string      $origin_country ISO 3166-1 (pode vir vazio)
     */
    public function __construct(
        public private(set) int $id,
        public private(set) ?string $logo_path,
        public private(set) string $name,
        public private(set) string $origin_country,
    ) {
    }
}
