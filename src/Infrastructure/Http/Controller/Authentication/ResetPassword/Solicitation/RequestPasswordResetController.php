<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Authentication\ResetPassword\Solicitation;

use App\Application\Authentication\DTO\ResetPassword\Solicitation\Request\RequestPasswordResetRequest;
use App\Application\Authentication\UseCase\ResetPassword\Solicitation\RequestPasswordResetUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use App\Infrastructure\Http\Request\JsonBody;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for requesting a password reset link
 *
 * POST /{_locale}/api/reset-password  {"email": ""}
 *
 * NOTA (out/2026):
 * - Era ResetPasswordSolicitationController; renomeado para espelhar o
 *   RequestPasswordResetUseCase (a pasta continua Solicitation).
 * - Sem os 6 blocos catch: cada um montava um JSON de erro próprio (e
 *   EmailDeliveryException virava 500). As exceções seguem para o
 *   ExceptionListener (403, 429, 503...).
 * - userAgent e ipAddress saíram do Request DTO: só iam para o contexto
 *   do email, e o template nunca os usou.
 */
#[AsController]
final class RequestPasswordResetController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Authentication\UseCase\ResetPassword\Solicitation\RequestPasswordResetUseCase $requestPasswordResetUseCase
     */
    public function __construct(
        private readonly RequestPasswordResetUseCase $requestPasswordResetUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $body = JsonBody::fromRequest($request);

        $response = $this->requestPasswordResetUseCase->execute(
            new RequestPasswordResetRequest(
                email: $body->string("email"),
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
