<?php

declare(strict_types = 1);

namespace App\Application\Image\DTO\Content\Request;

use App\Application\Image\Shared\ImageFormat;
use App\Application\Image\Shared\ImageQuality;
use App\Application\Image\Shared\ImageSize;
use App\Application\Image\Shared\Validation\ImageEndpoint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do GetImageUseCase: qual imagem do TMDB (caminho do arquivo),
 * em que tamanho, em que formato de resposta e, opcionalmente, com que
 * qualidade JPEG.
 *
 * Usado pelas duas rotas de imagem: /image/{size}/{endpoint} e
 * /image/{endpoint} (tamanho "original").
 *
 * NOTA (out/2026):
 * - Movido de DTO/ para DTO/Content/Request/, espelhando os outros
 *   módulos (Request/Response por feature).
 * - Validação: o caminho usa a constraint ImageEndpoint (antes cada
 *   controller tinha a sua), o tamanho usa o catálogo ImageSize (antes
 *   faltavam w45 e w300, que GET /images/sizes anunciava) e `quality`
 *   ganhou o intervalo de ImageQuality (antes qualquer inteiro passava).
 * - `size` deixou de ser nullable: as duas rotas sempre informam o
 *   tamanho, e null quebrava o TmdbApiService::getImage() (TypeError).
 * - Saíram `cache` (é cabeçalho HTTP; o controller lê ?cache= direto) e
 *   `fallbackUrl` (só existia para o executeWithFallback(), que ninguém
 *   chamava), além das propriedades virtuais, do asArray/toArray() e dos
 *   getters de compatibilidade.
 */
final class GetImageRequest {
    /**
     * @param string   $endpoint caminho do arquivo no TMDB ("/abc.jpg")
     * @param string   $size     um de ImageSize::ALL
     * @param string   $format   um de ImageFormat::ALL
     * @param int|null $quality  qualidade JPEG (ImageQuality::MIN a ImageQuality::MAX); null = não recomprimir
     */
    public function __construct(
        #[ImageEndpoint]
        public private(set) string $endpoint {
            /**
             * @return string
             */ get => $this->endpoint;
        },

        #[
            Assert\Choice(
                choices: ImageSize::ALL,
                message: 'Invalid image size. Allowed sizes: {{ choices }}',
            ),
        ]
        public private(set) string $size = ImageSize::ORIGINAL {
            /**
             * @return string
             */ get => $this->size;
        },

        #[
            Assert\Choice(
                choices: ImageFormat::ALL,
                message: 'Invalid response format. Allowed formats: {{ choices }}',
            ),
        ]
        public private(set) string $format = ImageFormat::BASE64 {
            /**
             * @return string
             */ get => $this->format;
        },

        #[
            Assert\Range(
                notInRangeMessage: 'Quality must be between {{ min }} and {{ max }}',
                min: ImageQuality::MIN,
                max: ImageQuality::MAX,
            ),
        ]
        public private(set) ?int $quality = null {
            /**
             * @return int|null
             */ get => $this->quality;
        },
    ) {
    }
}
