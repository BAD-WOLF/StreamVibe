<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\System\Logs\List;

use App\Application\System\UseCase\Logs\List\ListLogFilesUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for listing the log files
 *
 * GET /{_locale}/api/system/logs  (ROLE_DEV)
 *
 * Sem parâmetros: o UseCase lê tudo na hora.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
#[AsController]
final class ListLogFilesController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\System\UseCase\Logs\List\ListLogFilesUseCase $listLogFilesUseCase
     */
    public function __construct(
        private readonly ListLogFilesUseCase $listLogFilesUseCase,
    ) {}

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        $response = $this->listLogFilesUseCase->execute();

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
