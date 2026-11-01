<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Image\Original;

use App\Application\Image\DTO\Content\Request\GetImageRequest;
use App\Application\Image\Shared\ImageFormat;
use App\Application\Image\Shared\ImageSize;
use App\Application\Image\UseCase\Content\GetImageUseCase;
use App\Infrastructure\Http\Controller\Image\Shared\AbstractImageController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for getting a TMDB image in its original size
 *
 * GET /{_locale}/api/image/{endpoint}?format=binary|base64|url&quality=&cache=
 *
 * Mesmo UseCase da rota com tamanho (GetImageUseCase), com tamanho
 * "original" e formato padrão binary.
 *
 * NOTA (out/2026):
 * - Era ImageOriginalController em Controller/Image/; virou
 *   GetOriginalImageController em Controller/Image/Original/.
 * - Sem #[Route] e sem try/catch, pelos mesmos motivos do
 *   GetImageController (o #[Route] ainda apontava para uma operação
 *   "get_original_image" que nem existia — a da EntryPoint se chama
 *   "get_image_original").
 * - O formato não era validado (format=xyz chegava ao UseCase e só falhava
 *   lá, com o TypeError da validação). Agora é 400 pelo GetImageRequest.
 */
#[AsController]
final class GetOriginalImageController extends AbstractImageController
{
    /**
     * @param \App\Application\Image\UseCase\Content\GetImageUseCase $getImageUseCase
     */
    public function __construct(
        private readonly GetImageUseCase $getImageUseCase,
    ) {}

    public function __invoke(string $endpoint, Request $request): Response
    {
        $response = $this->getImageUseCase->execute(
            new GetImageRequest(
                endpoint: '/' . ltrim($endpoint, '/'),
                size: ImageSize::ORIGINAL,
                format: $request->query->getString('format', ImageFormat::BINARY),
                quality: $this->qualityFrom($request),
            ),
        );

        return $this->respondWithImage($response, $request);
    }
}
