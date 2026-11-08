<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin\User\List;

use App\Application\Admin\DTO\User\List\Request\ListUsersRequest;
use App\Application\Admin\UseCase\User\List\ListUsersUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for listing the accounts
 *
 * GET /{_locale}/api/admin/users  (ROLE_ADMIN)
 *
 * Os filtros vêm da query string (page, search, role, verified, blocked);
 * um filtro ausente não filtra. verified e blocked aceitam true/false
 * (1/0, yes/no, on/off); outro valor é 400.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
#[AsController]
final class ListUsersController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Admin\UseCase\User\List\ListUsersUseCase $listUsersUseCase
     */
    public function __construct(
        private readonly ListUsersUseCase $listUsersUseCase,
    ) {}

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $response = $this->listUsersUseCase->execute(
            new ListUsersRequest(
                page: $request->query->getInt("page", 1),
                search: $request->query->has("search") ? $request->query->getString("search") : null,
                role: $request->query->has("role") ? $request->query->getString("role") : null,
                verified: $request->query->has("verified") ? $request->query->getBoolean("verified") : null,
                blocked: $request->query->has("blocked") ? $request->query->getBoolean("blocked") : null,
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
