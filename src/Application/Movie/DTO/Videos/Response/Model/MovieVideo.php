<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Videos\Response\Model;

/**
 * Um vídeo do filme em `data.videos` (e, filtrado por tipo, em
 * `data.trailers`, `data.teasers` e `data.clips`) —
 * GET /movies/{movieId}/videos.
 *
 * Campos do TMDB + os links do YouTube (youtube_url e youtube_embed_url),
 * preenchidos só quando o vídeo está no YouTube.
 *
 * NOTA (out/2026): antes cada vídeo era um array montado pelo
 * GetMovieVideosUseCase::processVideo(). O JSON continua o mesmo (mesmas
 * chaves, mesma ordem); a classe dá tipo ao item e o documenta no Swagger.
 */
final class MovieVideo {
    /**
     * @param string      $id
     * @param string      $key               id do vídeo no site (ex.: id do YouTube)
     * @param string      $name
     * @param string      $site              "YouTube", "Vimeo"...
     * @param string      $type              "Trailer", "Teaser", "Clip", "Featurette"...
     * @param int         $size              resolução (360, 720, 1080...)
     * @param bool        $official
     * @param string|null $published_at      data/hora ISO 8601
     * @param string|null $youtube_url
     * @param string|null $youtube_embed_url
     */
    public function __construct(
        public private(set) string $id,
        public private(set) string $key,
        public private(set) string $name,
        public private(set) string $site,
        public private(set) string $type,
        public private(set) int $size,
        public private(set) bool $official,
        public private(set) ?string $published_at,
        public private(set) ?string $youtube_url,
        public private(set) ?string $youtube_embed_url,
    ) {
    }
}
