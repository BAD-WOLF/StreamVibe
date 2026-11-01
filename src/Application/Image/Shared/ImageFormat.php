<?php

declare(strict_types = 1);

namespace App\Application\Image\Shared;

/**
 * Formatos de resposta das rotas de imagem (parâmetro ?format=):
 *
 * - binary: a própria imagem (Content-Type da imagem);
 * - base64: JSON com a imagem em data URI ("data:image/jpeg;base64,...");
 * - url: redirect (302) para a imagem no CDN do TMDB.
 *
 * NOTA (out/2026): a lista existia repetida no GetImageRequest, no
 * ImageController (validação manual) e no GetImageSizesUseCase. Agora
 * todos leem daqui.
 */
final class ImageFormat {
    public const string BINARY = 'binary';

    public const string BASE64 = 'base64';

    public const string URL = 'url';

    public const array ALL = [self::BINARY, self::BASE64, self::URL];
}
