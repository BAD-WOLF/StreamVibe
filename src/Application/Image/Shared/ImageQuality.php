<?php

declare(strict_types = 1);

namespace App\Application\Image\Shared;

/**
 * Limites do parâmetro ?quality= das rotas de imagem: qualidade JPEG para
 * recomprimir a imagem antes de devolvê-la (binary e base64). Só vale
 * para JPEG; PNG/WebP/GIF voltam como vieram do TMDB. Sem ?quality=, a
 * imagem não é recomprimida.
 *
 * NOTA (out/2026): GET /images/sizes anunciava quality_options
 * {min: 1, max: 100, default: 85}, mas nada validava o valor (quality=500
 * passava direto para o GD), o "default" 85 nunca era aplicado, e a
 * recompressão convertia qualquer imagem para JPEG mantendo o
 * Content-Type original (um PNG saía como JPEG rotulado image/png). Agora
 * o GetImageRequest valida o intervalo daqui, só JPEG é recomprimido, e o
 * "default" saiu do quality_options.
 */
final class ImageQuality {
    public const int MIN = 1;

    public const int MAX = 100;
}
