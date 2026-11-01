<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Image\Content;

use App\Application\Image\DTO\Content\Request\GetImageRequest;
use App\Application\Image\Shared\ImageFormat;
use App\Application\Image\UseCase\Content\GetImageUseCase;
use App\Infrastructure\Http\Controller\Image\Shared\AbstractImageController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for getting a TMDB image in a given size
 *
 * GET /{_locale}/api/image/{size}/{endpoint}?format=base64|binary|url&quality=&cache=
 *
 * Formato padrão: base64 (JSON).
 *
 * NOTA (out/2026):
 * - Era ImageController em Controller/Image/; virou GetImageController em
 *   Controller/Image/Content/, espelhando
 *   Application/Image/{DTO,UseCase}/Content.
 * - Sem #[Route] (registrava uma rota extra em "/"; a rota real é a
 *   operação do API Platform, GetImageEntryPoint) e sem try/catch (o
 *   catch (Exception) devolvia 500 com a mensagem crua da exceção para o
 *   base64, e 404 text/plain para o binary). Erros saem do
 *   ExceptionListener com o status certo.
 * - A validação de caminho, formato e qualidade saiu daqui: fica no
 *   GetImageRequest (400 com o campo). A resposta por formato fica no
 *   AbstractImageController (compartilhado com o GetOriginalImageController).
 * - ?quality= inválido (texto) responde 400; antes virava null em silêncio.
 */
#[AsController]
final class GetImageController extends AbstractImageController
{
    /**
     * @param \App\Application\Image\UseCase\Content\GetImageUseCase $getImageUseCase
     */
    public function __construct(
        private readonly GetImageUseCase $getImageUseCase,
    ) {}

    public function __invoke(
        string $size,
        string $endpoint,
        Request $request,
    ): Response {
        $response = $this->getImageUseCase->execute(
            new GetImageRequest(
                endpoint: '/' . ltrim($endpoint, '/'),
                size: $size,
                format: $request->query->getString('format', ImageFormat::BASE64),
                quality: $this->qualityFrom($request),
            ),
        );

        return $this->respondWithImage($response, $request);
    }
}
