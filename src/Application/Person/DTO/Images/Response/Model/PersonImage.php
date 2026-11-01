<?php

declare(strict_types = 1);

namespace App\Application\Person\DTO\Images\Response\Model;

/**
 * Uma foto de perfil em `data.profiles` de GET /person/{personId}/images:
 * campos do TMDB + as URLs prontas nos tamanhos de foto de perfil do TMDB
 * (original, w185 e h632).
 *
 * NOTA (out/2026): criado com o endpoint de imagens de pessoa, que antes
 * existia só como placeholder (sempre null) no GetPersonDetailsUseCase.
 * Espelha o MovieImage (mesmos campos do TMDB), com os tamanhos de foto
 * de perfil no lugar dos de pôster/backdrop.
 */
final class PersonImage {
    /**
     * @param string      $file_path
     * @param int         $width
     * @param int         $height
     * @param float       $aspect_ratio
     * @param float       $vote_average
     * @param int         $vote_count
     * @param string|null $iso_639_1    idioma da imagem (null = sem texto)
     * @param string|null $url_original
     * @param string|null $url_w185
     * @param string|null $url_h632
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
        public private(set) ?string $url_w185,
        public private(set) ?string $url_h632,
    ) {
    }
}
