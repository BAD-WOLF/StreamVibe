<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Popular\Request;

use App\Application\Shared\Validation\LanguageCode;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do GetPopularMoviesUseCase.
 *
 * NOTA (out/2026): novo. Antes o UseCase recebia só `int $page` e o
 * controller corrigia página inválida em silêncio (max(1, $page)); agora
 * página fora de 1..500 (o limite do TMDB) responde 400, como na busca.
 * `language` é opcional e vai para o TMDB.
 */
final class GetPopularMoviesRequest {
    /**
     * @param int         $page
     * @param string|null $language
     */
    public function __construct(
        #[
            Assert\Range(
                notInRangeMessage: "Page must be between {{ min }} and {{ max }}",
                min: 1,
                max: 500,
            ),
        ]
        public private(set) int $page = 1 {
            /**
             * @return int
             */ get => $this->page;
        },

        #[LanguageCode]
        public private(set) ?string $language = null {
            /**
             * @return string|null
             */ get => $this->language;
        },
    ) {
    }
}
