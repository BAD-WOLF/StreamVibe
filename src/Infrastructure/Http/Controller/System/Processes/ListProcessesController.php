<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\System\Processes;

use App\Application\System\UseCase\Processes\ListProcessesUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for listing the processes of the container
 *
 * GET /{_locale}/api/system/processes  (ROLE_DEV)
 *
 * Sem parâmetros: o UseCase lê tudo na hora.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
#[AsController]
final class ListProcessesController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\System\UseCase\Processes\ListProcessesUseCase $listProcessesUseCase
     */
    public function __construct(
        private readonly ListProcessesUseCase $listProcessesUseCase,
    ) {}

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        $response = $this->listProcessesUseCase->execute();

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
