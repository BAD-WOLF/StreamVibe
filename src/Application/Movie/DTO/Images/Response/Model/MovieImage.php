<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Images\Response\Model;

/**
 * Uma imagem do filme em `data.backdrops`, `data.posters` ou `data.logos`
 * (GET /movies/{movieId}/images).
 *
 * Campos do TMDB + as URLs prontas em três tamanhos (original, w500 e
 * w780), montadas pelo GetMovieImagesUseCase.
 *
 * NOTA (out/2026): antes cada imagem era um array montado pelo
 * GetMovieImagesUseCase::processImage(). O JSON continua o mesmo (mesmas
 * chaves, mesma ordem); a classe dá tipo ao item e o documenta no Swagger.
 */
final class MovieImage {
    /**
     * @param string      $file_path
     * @param int         $width
     * @param int         $height
     * @param float       $aspect_ratio
     * @param float       $vote_average
     * @param int         $vote_count
     * @param string|null $iso_639_1    idioma da imagem (null = sem texto)
     * @param string|null $url_original
     * @param string|null $url_w500
     * @param string|null $url_w780
     */
    public function __construct(
        public private(set) string $file_path,
        public private(set) int $width,
        public private(set) int $height,
        public private(set) float $aspect_ratio,
        public private(set) float $vote_average,
        public private(set) int $vote_count,
        public private(set) ?string $iso_639_1,
        public private(set) ?string $url_original,
        public private(set) ?string $url_w500,
        public private(set) ?string $url_w780,
    ) {
    }
}
