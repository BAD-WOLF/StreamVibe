<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Videos\Request;

use App\Application\Shared\Validation\LanguageCode;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do GetMovieVideosUseCase: o id do filme, com as mesmas constraints
 * de GetMovieDetailsRequest::$movieId. Dedicado a este endpoint — as
 * flags include* do GetMovieDetailsRequest não fazem sentido num
 * UseCase por endpoint.
 *
 * NOTA (out/2026):
 * - `language` (opcional) é repassado ao TMDB.
 *   Atenção: para vídeos o TMDB filtra pelo idioma (só trailers naquele
 *   idioma); sem `language` vêm todos.
 * - getMovieId(), asArray e toArray() saíram (getters de compatibilidade):
 *   quem usa lê $request->movieId direto.
 */
final class GetMovieVideosRequest {
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
