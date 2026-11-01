<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Authentication\ResetPassword\Cancel;

use App\Application\Authentication\DTO\ResetPassword\Cancel\Request\CancelPasswordResetRequest;
use App\Application\Authentication\UseCase\ResetPassword\Cancel\CancelPasswordResetUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use App\Infrastructure\Http\Request\JsonBody;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for cancelling a password reset request
 *
 * POST /{_locale}/api/reset-password/cancel  {"token": ""}
 *
 * NOTA (out/2026):
 * - Era CancelResetPasswordController; renomeado para espelhar o
 *   CancelPasswordResetUseCase.
 * - Sem os 3 blocos catch: RuntimeException e Exception viravam 500 com a
 *   mensagem crua. Token inexistente continua 404, agora pelo
 *   ExceptionListener.
 */
#[AsController]
final class CancelPasswordResetController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Authentication\UseCase\ResetPassword\Cancel\CancelPasswordResetUseCase $cancelPasswordResetUseCase
     */
    public function __construct(
        private readonly CancelPasswordResetUseCase $cancelPasswordResetUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $body = JsonBody::fromRequest($request);

        $response = $this->cancelPasswordResetUseCase->execute(
            new CancelPasswordResetRequest(
                token: $body->string("token"),
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
