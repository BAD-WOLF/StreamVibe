<?php

declare(strict_types = 1);

namespace App\Application\Shared;

/**
 * Converte o vote_average do TMDB (escala 0-10) em percentual (0-100).
 * Genérico de propósito, como o DateYearExtractor e o TmdbImageUrlBuilder:
 * o TMDB usa a mesma escala para filmes e séries, então serve às listas e
 * aos detalhes de filme e aos créditos de pessoa (filmes e séries).
 *
 * NOTA (out/2026): era App\Application\Movie\Shared\MovieRatingCalculator.
 * Saiu de Movie quando os créditos de TV de Person passaram a usá-lo (o
 * nome "Movie" deixou de descrever o que ele faz). Mesma conta.
 */
final class RatingPercentageCalculator {
    /**
     * @param float $voteAverage 0 a 10
     *
     * @return int 0 a 100
     */
    public function calculatePercentage(float $voteAverage): int {
        return (int) round(($voteAverage / 10) * 100);
    }
}
