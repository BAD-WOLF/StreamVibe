<?php

declare(strict_types = 1);

namespace App\Application\Movie\Shared;

/**
 * Converte vote_average da TMDB (escala 0-10) pra percentual (0-100).
 * Fica em Movie porque é especificamente sobre o voto de filme — mas
 * também é usado por Person quando ele processa créditos de filme.
 */
final class MovieRatingCalculator {
    /**
     * @param float $voteAverage
     *
     * @return int
     */
    public function calculatePercentage(float $voteAverage): int {
        return (int) round(($voteAverage / 10) * 100);
    }
}
