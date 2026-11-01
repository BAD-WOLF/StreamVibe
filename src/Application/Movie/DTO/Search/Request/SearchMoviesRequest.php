<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Search\Request;

use App\Application\Shared\Validation\LanguageCode;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do SearchMoviesUseCase.
 *
 * NOTA (out/2026):
 * - Movido de Application/Movie/DTO/ para DTO/Search/Request/, igual aos
 *   demais endpoints de filme (DTO/<Feature>/Request|Response).
 * - region, year e primaryReleaseYear eram aceitos e ignorados; agora vão
 *   para o TMDB (filtros reais de /search/movie). language é novo e
 *   opcional, como em todos os endpoints de conteúdo.
 * - page passou a ir até 500, o limite do próprio TMDB (acima disso ele
 *   devolve 400 de qualquer jeito).
 * - Os "getters de compatibilidade" e o asArray/toArray saíram: quem usa
 *   lê as propriedades direto.
 */
final class SearchMoviesRequest {
    /**
     * @param string      $query
     * @param int         $page
     * @param bool        $includeAdult
     * @param string|null $language
     * @param string|null $region
     * @param int|null    $year
     * @param int|null    $primaryReleaseYear
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Search query cannot be blank')]
        #[
            Assert\Length(
                min: 1,
                max: 255,
                minMessage: 'Search query must be at least {{ limit }} character long',
                maxMessage: 'Search query cannot be longer than {{ limit }} characters',
            ),
        ]
        public private(set) string $query {
            /**
             * @return string
             */ get => $this->query;
        },

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

        public private(set) bool $includeAdult = false {
            /**
             * @return bool
             */ get => $this->includeAdult;
        },

        #[LanguageCode]
        public private(set) ?string $language = null {
            /**
             * @return string|null
             */ get => $this->language;
        },

        #[
            Assert\Regex(
                pattern: '/^[A-Z]{2}$/',
                message: 'Region must be an ISO 3166-1 code (e.g. "BR")',
            ),
        ]
        public private(set) ?string $region = null {
            /**
             * @return string|null
             */ get => $this->region;
        },

        #[
            Assert\Range(
                notInRangeMessage: "Year must be between {{ min }} and {{ max }}",
                min: 1874,
                max: 2100,
            ),
        ]
        public private(set) ?int $year = null {
            /**
             * @return int|null
             */ get => $this->year;
        },

        #[
            Assert\Range(
                notInRangeMessage: "Primary release year must be between {{ min }} and {{ max }}",
                min: 1874,
                max: 2100,
            ),
        ]
        public private(set) ?int $primaryReleaseYear = null {
            /**
             * @return int|null
             */ get => $this->primaryReleaseYear;
        },
    ) {
    }
}
