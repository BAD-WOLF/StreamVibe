<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin\User\Verify;

use App\Application\Admin\DTO\User\Verify\Request\VerifyUserRequest;
use App\Application\Admin\UseCase\User\Verify\VerifyUserUseCase;
use App\Domain\User\Entity\User;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Controller for confirming the email of an account
 *
 * POST /{_locale}/api/admin/users/{userId}/verify  (ROLE_ADMIN)
 *
 * Informa ao UseCase a conta (URL) e quem está pedindo (o usuário do
 * JWT); quem pode o quê é regra do UseCase (UserAdministrationPolicy).
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
#[AsController]
final class VerifyUserController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Admin\UseCase\User\Verify\VerifyUserUseCase $verifyUserUseCase
     */
    public function __construct(
        private readonly VerifyUserUseCase $verifyUserUseCase,
    ) {}

    /**
     * @param int                          $userId a conta (URL)
     * @param \App\Domain\User\Entity\User $admin  o usuário do JWT
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function __invoke(
        int $userId,
        #[CurrentUser] User $admin,
    ): JsonResponse {
        $response = $this->verifyUserUseCase->execute(
            new VerifyUserRequest(
                userId: $userId,
                actorId: (int) $admin->getId(),
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
