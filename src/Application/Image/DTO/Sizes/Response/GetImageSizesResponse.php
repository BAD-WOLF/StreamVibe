<?php

declare(strict_types = 1);

namespace App\Application\Image\DTO\Sizes\Response;

use Symfony\Component\Serializer\Attribute\Ignore;

/**
 * Resposta do GetImageSizesUseCase: o `data` de GET /images/sizes.
 *
 *     {
 *       "supported_sizes": {"poster": ["w92", ...], "backdrop": [...], ...},
 *       "supported_formats": ["binary", "base64", "url"],
 *       "cache_enabled": true,
 *       "quality_options": {"min": 1, "max": 100},
 *       "usage_examples": {"poster_w500": "/pt_BR/api/image/w500/poster.jpg", ...}
 *     }
 *
 * As chaves ficam em snake_case na própria propriedade (é o contrato que
 * o endpoint já devolvia), como em GetMovieDetailsResponse.
 *
 * NOTA (out/2026):
 * - Movido de DTO/ para DTO/Sizes/Response/, espelhando os outros módulos.
 * - quality_options perdeu o "default": 85, que nunca era aplicado (sem
 *   ?quality= a imagem não é recomprimida — ver ImageQuality).
 * - usage_examples agora vêm com o locale e batem com as rotas (ver
 *   GetImageSizesRequest).
 * - Saíram success/errors, isSuccess(), getMessage(), getErrors() e o
 *   asArray/toArray(): o JSON sai do GetImageSizesOutput.
 */
final class GetImageSizesResponse {
    /**
     * @param array<string, list<string>> $supported_sizes   tamanhos por tipo de imagem
     * @param list<string>                $supported_formats
     * @param bool                        $cache_enabled     format=binary devolve Cache-Control (desligável com ?cache=false)
     * @param array{min: int, max: int}   $quality_options
     * @param array<string, string>       $usage_examples
     * @param string|null                 $message
     */
    public function __construct(
        public private(set) array $supported_sizes {
            /**
             * @return array<string, list<string>>
             */ get => $this->supported_sizes;
        },
        public private(set) array $supported_formats {
            /**
             * @return list<string>
             */ get => $this->supported_formats;
        },
        public private(set) bool $cache_enabled {
            /**
             * @return bool
             */ get => $this->cache_enabled;
        },
        public private(set) array $quality_options {
            /**
             * @return array{min: int, max: int}
             */ get => $this->quality_options;
        },
        public private(set) array $usage_examples {
            /**
             * @return array<string, string>
             */ get => $this->usage_examples;
        },
        // NOTA: message é envelope — o GetImageSizesOutput já a expõe no
        // nível dele; #[Ignore] evita repeti-la dentro de `data`.
        #[Ignore]
        public private(set) ?string $message = null {
            /**
             * @return string|null
             */ get => $this->message;
        },
    ) {
    }

    /**
     * @param array<string, list<string>> $supportedSizes
     * @param list<string>                $supportedFormats
     * @param bool                        $cacheEnabled
     * @param array{min: int, max: int}   $qualityOptions
     * @param array<string, string>       $usageExamples
     * @param string|null                 $message
     *
     * @return self
     */
    public static function success(
        array $supportedSizes,
        array $supportedFormats,
        bool $cacheEnabled,
        array $qualityOptions,
        array $usageExamples,
        ?string $message = null,
    ): self {
        return new self(
            supported_sizes: $supportedSizes,
            supported_formats: $supportedFormats,
            cache_enabled: $cacheEnabled,
            quality_options: $qualityOptions,
            usage_examples: $usageExamples,
            message: $message,
        );
    }
}
