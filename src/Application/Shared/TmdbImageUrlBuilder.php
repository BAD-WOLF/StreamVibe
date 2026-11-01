<?php

declare(strict_types = 1);

namespace App\Application\Shared;

/**
 * Constrói URLs completas de imagem a partir de um path relativo da
 * TMDB. Extraído de várias UseCases (Movie e Person) que
 * reimplementavam a mesma lógica.
 */
final class TmdbImageUrlBuilder {
    /**
     * @param string|null $path
     * @param string      $size
     *
     * @return string|null
     */
    public function build(?string $path, string $size = 'original'): ?string {
        if (empty($path)) {
            return null;
        }

        return "https://image.tmdb.org/t/p/{$size}{$path}";
    }
}
