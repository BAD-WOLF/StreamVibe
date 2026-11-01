<?php

declare(strict_types = 1);

namespace App\Application\Image\DTO\Info\Request;

use App\Application\Image\Shared\ImageSize;
use App\Application\Image\Shared\Validation\ImageEndpoint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do GetImageInfoUseCase: o caminho do arquivo no TMDB e o
 * tamanho desejado (padrão "original").
 *
 * NOTA (out/2026): criado na separação do GetImageUseCase. Antes o
 * ImageInfoController reaproveitava o GetImageRequest com format=url (e
 * validava o caminho com um validateEndpoint() próprio); agora a info tem
 * Request/UseCase/Response próprios, com as mesmas regras de caminho
 * (ImageEndpoint) e de tamanho (ImageSize) das rotas de imagem.
 */
final class GetImageInfoRequest {
    /**
     * @param string $endpoint caminho do arquivo no TMDB ("/abc.jpg")
     * @param string $size     um de ImageSize::ALL
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
    ) {
    }
}
