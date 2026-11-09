<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\System\Dependencies;

use App\Application\System\UseCase\Dependencies\GetDependenciesStatusUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for checking the dependencies of the API
 *
 * GET /{_locale}/api/system/dependencies  (ROLE_DEV)
 *
 * Sem parâmetros: o UseCase lê tudo na hora.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
#[AsController]
final class GetDependenciesStatusController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\System\UseCase\Dependencies\GetDependenciesStatusUseCase $getDependenciesStatusUseCase
     */
    public function __construct(
        private readonly GetDependenciesStatusUseCase $getDependenciesStatusUseCase,
    ) {}

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        $response = $this->getDependenciesStatusUseCase->execute();

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
