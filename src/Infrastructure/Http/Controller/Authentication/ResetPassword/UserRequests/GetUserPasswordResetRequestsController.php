<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Authentication\ResetPassword\UserRequests;

use App\Application\Authentication\DTO\ResetPassword\UserRequests\Request\GetUserPasswordResetRequestsRequest;
use App\Application\Authentication\UseCase\ResetPassword\UserRequests\GetUserPasswordResetRequestsUseCase;
use App\Domain\User\Entity\User;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Controller for listing the active password reset requests of a user
 *
 * GET /{_locale}/api/reset-password/requests/{userId}  (exige JWT)
 *
 * Informa ao UseCase quem está pedindo (o usuário do JWT e se ele tem
 * ROLE_ADMIN); a regra "só o próprio usuário ou um admin" é do UseCase,
 * que responde 403 para os outros.
 *
 * NOTA (out/2026):
 * - Era GetUserResetRequestsController; renomeado para espelhar o
 *   GetUserPasswordResetRequestsUseCase.
 * - Corrige o IDOR: a rota passava só o userId da URL, e qualquer
 *   usuário logado via as solicitações de qualquer outro.
 * - A resposta sai pelo auto-mapping (GetUserPasswordResetRequestsOutput),
 *   e não mais de um array montado aqui. O catch (Exception) que
 *   devolvia 500 com a mensagem crua saiu.
 */
#[AsController]
final class GetUserPasswordResetRequestsController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Authentication\UseCase\ResetPassword\UserRequests\GetUserPasswordResetRequestsUseCase $getUserPasswordResetRequestsUseCase
     */
    public function __construct(
        private readonly GetUserPasswordResetRequestsUseCase $getUserPasswordResetRequestsUseCase,
    ) {}

    /**
     * @param int                          $userId    de quem são as solicitações (URL)
     * @param \App\Domain\User\Entity\User $requester o usuário do JWT
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function __invoke(
        int $userId,
        #[CurrentUser] User $requester,
    ): JsonResponse {
        $response = $this->getUserPasswordResetRequestsUseCase->execute(
            new GetUserPasswordResetRequestsRequest(
                userId: $userId,
                requesterId: (int) $requester->getId(),
                requesterIsAdmin: $this->isGranted("ROLE_ADMIN"),
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
