<?php

declare(strict_types = 1);

namespace App\Application\Image\UseCase\Sizes;

use App\Application\Image\DTO\Sizes\Request\GetImageSizesRequest;
use App\Application\Image\DTO\Sizes\Response\GetImageSizesResponse;
use App\Application\Image\Shared\ImageFormat;
use App\Application\Image\Shared\ImageQuality;
use App\Application\Image\Shared\ImageSize;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Lista os tamanhos e formatos de imagem que a API suporta. É dado
 * estático: não há chamada ao TMDB.
 *
 * NOTA (out/2026): criado para tirar do ImageSizesController o mapa de
 * tamanhos que estava hardcoded lá — controller não deve conhecer dado
 * de negócio — e para fechar o mesmo padrão dos demais endpoints
 * (Response DTO → UseCase → Output). Só tem execute(), como os outros
 * UseCases.
 *
 * Depois, na reescrita do módulo Image:
 * - Tamanhos, formatos e limites de qualidade vêm dos catálogos
 *   ImageSize, ImageFormat e ImageQuality — os mesmos que as rotas e o
 *   GetImageRequest usam. Antes este UseCase tinha listas próprias (com
 *   w45/w300, que a rota recusava) e o quality_options anunciava um
 *   "default" 85 que nunca era aplicado.
 * - usage_examples vêm do GetImageSizesRequest (montados pelo controller
 *   com o router): os fixos daqui não tinham o /{_locale} e não
 *   funcionavam.
 */
final readonly class GetImageSizesUseCase {
    /**
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator
     */
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Image\DTO\Sizes\Request\GetImageSizesRequest $request
     *
     * @return \App\Application\Image\DTO\Sizes\Response\GetImageSizesResponse
     */
    public function execute(GetImageSizesRequest $request): GetImageSizesResponse {
        return GetImageSizesResponse::success(
            supportedSizes: ImageSize::BY_TYPE,
            supportedFormats: ImageFormat::ALL,
            cacheEnabled: true,
            qualityOptions: [
                'min' => ImageQuality::MIN,
                'max' => ImageQuality::MAX,
            ],
            usageExamples: $request->usageExamples,
            message: $this->translator->trans(
                'Supported image sizes and formats retrieved successfully',
            ),
        );
    }
}
