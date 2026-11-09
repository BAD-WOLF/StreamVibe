<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\System\Logs\Read;

use App\Application\System\DTO\Logs\Read\Request\ReadLogFileRequest;
use App\Application\System\UseCase\Logs\Read\ReadLogFileUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for reading a log file
 *
 * GET /{_locale}/api/system/logs/{file}  (ROLE_DEV)
 *
 * O arquivo vem da URL; os filtros, da query string (limit, level,
 * search) — um filtro ausente não filtra. A validação do nome (só
 * nomes de log, nada fora da pasta) e dos filtros é do UseCase.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
#[AsController]
final class ReadLogFileController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\System\UseCase\Logs\Read\ReadLogFileUseCase $readLogFileUseCase
     */
    public function __construct(
        private readonly ReadLogFileUseCase $readLogFileUseCase,
    ) {}

    /**
     * @param string                                    $file    o nome do arquivo (URL)
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function __invoke(
        string $file,
        Request $request,
    ): JsonResponse {
        $response = $this->readLogFileUseCase->execute(
            new ReadLogFileRequest(
                file: $file,
                limit: $request->query->getInt("limit", 200),
                level: $request->query->has("level") ? $request->query->getString("level") : null,
                search: $request->query->has("search") ? $request->query->getString("search") : null,
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
