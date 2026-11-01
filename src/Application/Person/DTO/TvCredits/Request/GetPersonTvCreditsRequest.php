<?php

declare(strict_types = 1);

namespace App\Application\Person\DTO\TvCredits\Request;

use App\Application\Shared\Validation\LanguageCode;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do GetPersonTvCreditsUseCase: o id da pessoa no TMDB e o idioma
 * opcional do conteúdo (nomes e sinopses das séries).
 *
 * NOTA (out/2026): criado com o endpoint de créditos de TV
 * (GET /person/{personId}/tv/credits), que antes era só um placeholder
 * sempre null no GetPersonDetailsUseCase. Espelha o
 * GetPersonMovieCreditsRequest.
 */
final class GetPersonTvCreditsRequest {
    /**
     * @param int         $personId
     * @param string|null $language
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Person ID cannot be blank')]
        #[
            Assert\Range(
                notInRangeMessage: "Person ID must be between {{ min }} and {{ max }}",
                min: 1,
                max: 999999999,
            ),
        ]
        public private(set) int $personId {
            /**
             * @return int
             */ get => $this->personId;
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
