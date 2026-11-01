<?php

declare(strict_types = 1);

namespace App\Application\Image\UseCase\Info;

use App\Application\Image\DTO\Info\Request\GetImageInfoRequest;
use App\Application\Image\DTO\Info\Response\GetImageInfoResponse;
use App\Application\Shared\RequestValidator;
use App\Application\Shared\TmdbImageUrlBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Informações de uma imagem do TMDB sem baixá-la: a URL no CDN para o
 * tamanho pedido e a extensão do arquivo. Não consulta o TMDB — a URL é
 * montada, não verificada.
 *
 * NOTA (out/2026): criado na separação do GetImageUseCase. Antes o
 * ImageInfoController chamava o GetImageUseCase com format=url e
 * devolvia o toArray() da resposta, quase todo null (mime_type, size,
 * content_length, file_extension). Validação pelo RequestValidator, como
 * os outros UseCases (caminho e tamanho inválidos = 400).
 */
final readonly class GetImageInfoUseCase {
    /**
     * @param \App\Application\Shared\RequestValidator           $requestValidator
     * @param \App\Application\Shared\TmdbImageUrlBuilder        $imageUrlBuilder
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator
     */
    public function __construct(
        private RequestValidator $requestValidator,
        private TmdbImageUrlBuilder $imageUrlBuilder,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Image\DTO\Info\Request\GetImageInfoRequest $request
     *
     * @return \App\Application\Image\DTO\Info\Response\GetImageInfoResponse
     * @throws \App\Domain\Exception\UserValidationException
     */
    public function execute(GetImageInfoRequest $request): GetImageInfoResponse {
        $this->requestValidator->validate(
            $request,
            'image_info_request',
            ['endpoint' => $request->endpoint, 'size' => $request->size],
        );

        return GetImageInfoResponse::success(
            endpoint: $request->endpoint,
            size: $request->size,
            url: (string) $this->imageUrlBuilder->build($request->endpoint, $request->size),
            fileExtension: strtolower(pathinfo($request->endpoint, PATHINFO_EXTENSION)),
            message: $this->translator->trans('Image information retrieved successfully'),
        );
    }
}
