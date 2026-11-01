<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Details\Response\Model;

/**
 * Um idioma falado no filme em `data.spoken_languages`
 * (GET /movies/{movieId}).
 *
 * NOTA (out/2026): a classe existia mas nunca era instanciada (o campo
 * saía como o array cru do TMDB). Agora o GetMovieDetailsUseCase monta
 * cada item; o JSON é o mesmo. Propriedades em `public private(set)` no
 * construtor, como os outros Response/Model; o #[ApiProperty] vazio saiu
 * (não tinha efeito e prendia o Application ao API Platform).
 */
final class SpokenLanguage {
    /**
     * @param string $english_name
     * @param string $iso_639_1
     * @param string $name         nome no próprio idioma
     */
    public function __construct(
        public private(set) string $english_name,
        public private(set) string $iso_639_1,
        public private(set) string $name,
    ) {
    }
}
