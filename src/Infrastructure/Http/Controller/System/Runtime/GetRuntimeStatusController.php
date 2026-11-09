<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\System\Runtime;

use App\Application\System\UseCase\Runtime\GetRuntimeStatusUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for the PHP and Symfony runtime
 *
 * GET /{_locale}/api/system/runtime  (ROLE_DEV)
 *
 * Sem parâmetros: o UseCase lê tudo na hora.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
#[AsController]
final class GetRuntimeStatusController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\System\UseCase\Runtime\GetRuntimeStatusUseCase $getRuntimeStatusUseCase
     */
    public function __construct(
        private readonly GetRuntimeStatusUseCase $getRuntimeStatusUseCase,
    ) {}

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        $response = $this->getRuntimeStatusUseCase->execute();

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
