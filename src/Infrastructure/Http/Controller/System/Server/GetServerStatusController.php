<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\System\Server;

use App\Application\System\UseCase\Server\GetServerStatusUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for the status of the server
 *
 * GET /{_locale}/api/system/server  (ROLE_DEV)
 *
 * Sem parâmetros: o UseCase lê tudo na hora.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
#[AsController]
final class GetServerStatusController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\System\UseCase\Server\GetServerStatusUseCase $getServerStatusUseCase
     */
    public function __construct(
        private readonly GetServerStatusUseCase $getServerStatusUseCase,
    ) {}

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        $response = $this->getServerStatusUseCase->execute();

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
