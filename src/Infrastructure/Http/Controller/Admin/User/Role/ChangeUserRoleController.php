<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin\User\Role;

use App\Application\Admin\DTO\User\Role\Request\ChangeUserRoleRequest;
use App\Application\Admin\UseCase\User\Role\ChangeUserRoleUseCase;
use App\Domain\User\Entity\User;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use App\Infrastructure\Http\Request\JsonBody;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Controller for changing the role of an account
 *
 * POST /{_locale}/api/admin/users/{userId}/role  (ROLE_ADMIN)
 *
 * Informa ao UseCase a conta (URL) e quem está pedindo (o usuário do
 * JWT); quem pode o quê é regra do UseCase (UserAdministrationPolicy).
 * Corpo: {"role": "..."} (JsonBody).
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
#[AsController]
final class ChangeUserRoleController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Admin\UseCase\User\Role\ChangeUserRoleUseCase $changeUserRoleUseCase
     */
    public function __construct(
        private readonly ChangeUserRoleUseCase $changeUserRoleUseCase,
    ) {}

    /**
     * @param int                                       $userId  a conta (URL)
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Domain\User\Entity\User              $admin   o usuário do JWT
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function __invoke(
        int $userId,
        Request $request,
        #[CurrentUser] User $admin,
    ): JsonResponse {
        $body = JsonBody::fromRequest($request);

        $response = $this->changeUserRoleUseCase->execute(
            new ChangeUserRoleRequest(
                userId: $userId,
                actorId: (int) $admin->getId(),
                role: $body->string("role"),
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
