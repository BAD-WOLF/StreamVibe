<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Similar\Request;

use App\Application\Shared\Validation\LanguageCode;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do GetMovieSimilarUseCase: o id do filme, com as mesmas constraints
 * de GetMovieDetailsRequest::$movieId. Dedicado a este endpoint — as
 * flags include* do GetMovieDetailsRequest não fazem sentido num
 * UseCase por endpoint.
 *
 * NOTA (out/2026):
 * - `language` (opcional) é repassado ao TMDB.
 * - `page` é novo: a resposta já trazia paginação, mas não havia como
 *   pedir outra página (o TMDB era sempre chamado com a página 1).
 * - getMovieId(), asArray e toArray() saíram (getters de compatibilidade):
 *   quem usa lê $request->movieId direto.
 */
final class GetMovieSimilarRequest {
    /**
     * @param int         $movieId
     * @param int         $page
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
