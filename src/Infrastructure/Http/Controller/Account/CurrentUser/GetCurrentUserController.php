<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Account\CurrentUser;

use App\Application\Account\DTO\CurrentUser\Request\GetCurrentUserRequest;
use App\Application\Account\UseCase\CurrentUser\GetCurrentUserUseCase;
use App\Domain\User\Entity\User;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Controller for the account of the logged-in user
 *
 * GET /{_locale}/api/me  (exige JWT)
 *
 * Passa ao UseCase só o id do usuário do JWT: a rota não tem parâmetro, e
 * não há como pedir por ela os dados de outra conta.
 *
 * NOTA (nov/2026): criado com o GET /me.
 */
#[AsController]
final class GetCurrentUserController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Account\UseCase\CurrentUser\GetCurrentUserUseCase $getCurrentUserUseCase
     */
    public function __construct(
        private readonly GetCurrentUserUseCase $getCurrentUserUseCase,
    ) {}

    /**
     * @param \App\Domain\User\Entity\User $user o usuário do JWT
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $response = $this->getCurrentUserUseCase->execute(
            new GetCurrentUserRequest(
                userId: (int) $user->getId(),
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
