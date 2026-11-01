<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Trending\Request;

use App\Application\Shared\Validation\LanguageCode;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do GetTrendingMoviesUseCase.
 *
 * NOTA (out/2026): novo. Antes o UseCase recebia (string $timeWindow,
 * int $page) soltos e o controller lia o time window de
 * ?time_window= — ignorando o {timeWindow} da própria rota
 * (/movies/trending/{timeWindow}). Agora o controller monta este DTO com o
 * valor da rota, e a regra day|week fica aqui (além do requirement da
 * rota).
 */
final class GetTrendingMoviesRequest {
    /**
     * @param string      $timeWindow
     * @param int         $page
     * @param string|null $language
     */
    public function __construct(
        #[
            Assert\Choice(
                choices: ['day', 'week'],
                message: 'Time window must be "day" or "week"',
            ),
        ]
        public private(set) string $timeWindow = 'week' {
            /**
             * @return string
             */ get => $this->timeWindow;
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
