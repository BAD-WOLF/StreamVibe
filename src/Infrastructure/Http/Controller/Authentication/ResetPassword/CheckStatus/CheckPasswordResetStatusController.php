<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Authentication\ResetPassword\CheckStatus;

use App\Application\Authentication\DTO\ResetPassword\CheckStatus\Request\CheckPasswordResetStatusRequest;
use App\Application\Authentication\UseCase\ResetPassword\CheckStatus\CheckPasswordResetStatusUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for checking a password reset token (the link sent by email)
 *
 * GET /{_locale}/api/reset-password/status/{token}
 *
 * NOTA (out/2026):
 * - Era CheckResetStatusController; renomeado para espelhar o
 *   CheckPasswordResetStatusUseCase.
 * - Sem os 4 blocos catch (cada um com um JSON de erro próprio) e sem os
 *   logs com o começo do token. Token inválido é 400 e vencido 410, pelo
 *   ExceptionListener.
 */
#[AsController]
final class CheckPasswordResetStatusController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Authentication\UseCase\ResetPassword\CheckStatus\CheckPasswordResetStatusUseCase $checkPasswordResetStatusUseCase
     */
    public function __construct(
        private readonly CheckPasswordResetStatusUseCase $checkPasswordResetStatusUseCase,
    ) {}

    public function __invoke(string $token): JsonResponse
    {
        $response = $this->checkPasswordResetStatusUseCase->execute(
            new CheckPasswordResetStatusRequest(
                token: $token,
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
