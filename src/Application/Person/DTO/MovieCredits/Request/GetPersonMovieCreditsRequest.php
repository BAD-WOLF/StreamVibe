<?php

declare(strict_types = 1);

namespace App\Application\Person\DTO\MovieCredits\Request;

use App\Application\Shared\Validation\LanguageCode;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do GetPersonMovieCreditsUseCase: o id da pessoa no TMDB e o
 * idioma opcional do conteúdo (títulos e sinopses dos filmes).
 *
 * NOTA (out/2026):
 * - Movido de DTO/Credits/ para DTO/MovieCredits/Request/: com os
 *   créditos de TV (DTO/TvCredits), "Credits" sozinho ficou ambíguo; e
 *   Request/Response em pastas próprias, como no módulo Movie.
 * - Ganhou `language` (LanguageCode), repassado ao TMDB.
 * - getPersonId() (getter de compatibilidade) saiu: quem usa lê
 *   $request->personId direto.
 */
final class GetPersonMovieCreditsRequest {
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
