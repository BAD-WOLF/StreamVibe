<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Details\Request;

use App\Application\Shared\Validation\LanguageCode;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do GetMovieDetailsUseCase.
 *
 * NOTA (out/2026): `language` agora chega ao TMDB (antes era aceito aqui
 * e nunca enviado). `region` saiu: /movie/{id} do TMDB não tem esse
 * filtro, então ele nunca teria efeito. Os getters de compatibilidade e o
 * asArray/toArray também saíram.
 */
final class GetMovieDetailsRequest {
    /**
     * @param int         $movieId
     * @param string|null $language
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Movie ID cannot be blank')]
        #[
            Assert\Range(
                notInRangeMessage: "Movie ID must be between {{ min }} and {{ max }}",
                min: 1,
                max: 999999999,
            ),
        ]
        public private(set) int $movieId {
            /**
             * @return int
             */ get => $this->movieId;
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
