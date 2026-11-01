<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Details\Response\Model;

/**
 * Um país de produção em `data.production_countries`
 * (GET /movies/{movieId}).
 *
 * NOTA (out/2026): a classe existia mas nunca era instanciada (o campo
 * saía como o array cru do TMDB). Agora o GetMovieDetailsUseCase monta
 * cada item; o JSON é o mesmo. Propriedades em `public private(set)` no
 * construtor, como os outros Response/Model; o #[ApiProperty] vazio saiu
 * (não tinha efeito e prendia o Application ao API Platform).
 */
final class ProductionCountry {
    /**
     * @param string $iso_3166_1
     * @param string $name
     */
    public function __construct(
        public private(set) string $iso_3166_1,
        public private(set) string $name,
    ) {
    }
}
