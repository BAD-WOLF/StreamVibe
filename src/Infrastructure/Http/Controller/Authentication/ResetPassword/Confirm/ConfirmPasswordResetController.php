<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Authentication\ResetPassword\Confirm;

use App\Application\Authentication\DTO\ResetPassword\Confirm\Request\ConfirmPasswordResetRequest;
use App\Application\Authentication\UseCase\ResetPassword\Confirm\ConfirmPasswordResetUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use App\Infrastructure\Http\Request\JsonBody;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for setting the new password with the token of the reset link
 *
 * POST /{_locale}/api/reset-password/confirm  {"token": "", "password": ""}
 *
 * NOTA (out/2026): endpoint novo. O fluxo de troca de senha permitia
 * pedir o link, conferir e cancelar, mas não definir a senha nova — o
 * link do email leva ao front (FRONTEND_UI_DOMAIN/auth/repassword/{token}),
 * que não tinha para onde mandar a senha.
 */
#[AsController]
final class ConfirmPasswordResetController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Authentication\UseCase\ResetPassword\Confirm\ConfirmPasswordResetUseCase $confirmPasswordResetUseCase
     */
    public function __construct(
        private readonly ConfirmPasswordResetUseCase $confirmPasswordResetUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $body = JsonBody::fromRequest($request);

        $response = $this->confirmPasswordResetUseCase->execute(
            new ConfirmPasswordResetRequest(
                token: $body->string("token"),
                password: $body->string("password"),
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
