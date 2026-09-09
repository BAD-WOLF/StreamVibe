<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Authentication\ResetPassword;

use App\Application\Authentication\UseCase\ResetUserPasswordUseCase;
use App\Domain\Exception\UserValidationException;
use App\Infrastructure\ApiPlatform\Auth\ResetPassword\UserRequests\UserResetRequestsEntryPoint;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;

/**
 * Controller for getting user reset password requests
 *
 * This controller uses the OutputMappingTrait to automatically map
 * Application DTOs to ApiPlatform Output Models via the auto-mapping system.
 *
 * NOTA (bug crítico, ago/2026): este controller chamava
 * $this->resetPasswordUseCase->getUserResetRequests(...), método que
 * nunca existiu. O método real é getUserActiveRequests(int $userId): array
 * — e repare que ele devolve um array puro, não um DTO. Isso significa que
 * mesmo corrigindo só o nome, safeMapToJsonResponse(dto: $response, ...)
 * continuaria quebrando: o parâmetro $dto do OutputMappingTrait exige
 * "object", e passar um array ali é TypeError, não Error de método
 * inexistente. Por isso a correção não usa mapeamento automático aqui —
 * não existe DTO de aplicação para essa resposta, é lista crua de
 * ['id', 'created_at', 'expires_at'] (ver
 * ResetUserPasswordUseCase::getUserActiveRequests()).
 */
#[AsController]
class GetUserResetRequestsController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Authentication\UseCase\ResetUserPasswordUseCase $resetPasswordUseCase
     * @param \Symfony\Contracts\Translation\TranslatorInterface               $translator
     */
    public function __construct(
        private readonly ResetUserPasswordUseCase $resetPasswordUseCase,
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * Get user reset password requests
     */
    #[
        Route(
            name: "api_user_reset_requests",
            defaults: [
                "_api_resource_class" => UserResetRequestsEntryPoint::class,
                "_api_operation_name" => "get_user_reset_requests",
            ],
            methods: ["GET"],
        ),
    ]
    public function __invoke(int $userId): JsonResponse
    {
        if ($userId <= 0) {
            throw UserValidationException::invalidFieldValue(
                "userId",
                $userId,
                "positive integer",
            );
        }

        try {
            // getUserActiveRequests() devolve array puro, não um DTO — ver
            // NOTA da classe. Sem mapeamento automático aqui.
            $activeRequests = $this->resetPasswordUseCase->getUserActiveRequests(
                userId: $userId,
            );

            return $this->json(
                [
                    "success" => true,
                    "data" => [
                        "user_id" => $userId,
                        "active_requests" => $activeRequests,
                        "count" => count($activeRequests),
                    ],
                    "message" => $this->translator->trans(
                        "User reset requests retrieved successfully",
                    ),
                ],
                200,
            );
        } catch (Exception $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $this->translator->trans(
                        "An error occurred while retrieving user reset requests",
                    ),
                    "message" => $e->getMessage(),
                    "user_id" => $userId,
                ],
                500,
            );
        }
    }
}

