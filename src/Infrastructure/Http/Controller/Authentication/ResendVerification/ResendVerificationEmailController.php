<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Authentication\ResendVerification;

use App\Application\Authentication\DTO\ResendVerification\Request\ResendVerificationEmailRequest;
use App\Application\Authentication\UseCase\ResendVerification\ResendVerificationEmailUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use App\Infrastructure\Http\Request\JsonBody;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for resending the email confirmation
 *
 * POST /{_locale}/api/resend-verification  {"email": ""}
 *
 * NOTA (out/2026):
 * - Era ResendVerificationController (em Controller/Authentication/, fora
 *   de uma pasta própria). Agora chama o ResendVerificationEmailUseCase,
 *   em vez de montar um RegisterUserRequest com senha vazia,
 *   agreeTerms=true e a flag resendVerification.
 * - A validação do email (antes três blocos manuais com ~10 logs de
 *   passo a passo) é a do ResendVerificationEmailRequest (400).
 * - É o único reenvio. O POST /api/verify/email/resend
 *   (EmailVerificationController) saiu: ele devolvia o link de
 *   verificação na resposta HTTP, e qualquer pessoa verificava o email
 *   de outra.
 */
#[AsController]
final class ResendVerificationEmailController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Authentication\UseCase\ResendVerification\ResendVerificationEmailUseCase $resendVerificationEmailUseCase
     */
    public function __construct(
        private readonly ResendVerificationEmailUseCase $resendVerificationEmailUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $body = JsonBody::fromRequest($request);

        $response = $this->resendVerificationEmailUseCase->execute(
            new ResendVerificationEmailRequest(
                email: $body->string("email"),
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
