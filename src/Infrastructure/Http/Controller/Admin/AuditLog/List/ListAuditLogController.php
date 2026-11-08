<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin\AuditLog\List;

use App\Application\Admin\DTO\AuditLog\List\Request\ListAuditLogRequest;
use App\Application\Admin\UseCase\AuditLog\List\ListAuditLogUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for listing the audit log
 *
 * GET /{_locale}/api/admin/audit-log  (ROLE_ADMIN)
 *
 * Os filtros vêm da query string (page, user_id, action); um filtro
 * ausente não filtra.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
#[AsController]
final class ListAuditLogController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Admin\UseCase\AuditLog\List\ListAuditLogUseCase $listAuditLogUseCase
     */
    public function __construct(
        private readonly ListAuditLogUseCase $listAuditLogUseCase,
    ) {}

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $response = $this->listAuditLogUseCase->execute(
            new ListAuditLogRequest(
                page: $request->query->getInt("page", 1),
                userId: $request->query->has("user_id") ? $request->query->getInt("user_id") : null,
                action: $request->query->has("action") ? $request->query->getString("action") : null,
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
