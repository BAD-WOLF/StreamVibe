<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Authentication\EmailVerification\Verify;

use App\Application\Authentication\DTO\EmailVerification\Verify\Request\VerifyEmailRequest;
use App\Application\Authentication\UseCase\EmailVerification\Verify\VerifyEmailUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for verifying the email address (the link sent by email)
 *
 * GET /{_locale}/api/verify/email?expires=&id=&signature=&token=
 *
 * O UseCase recebe a URL inteira ($request->getUri()) porque a
 * assinatura cobre o endereço completo; a ordem dos parâmetros não
 * importa para a conferência.
 *
 * NOTA (out/2026):
 * - Era EmailVerificationController::verifyUserEmail(), com
 *   #[Route("/api/verify/email")] fora do prefixo /{_locale}/api. Agora a
 *   rota é a operação do API Platform (VerifyEmailEntryPoint), com
 *   locale como as outras, e o link do email aponta para ela.
 * - Buscar o usuário, conferir o link e marcar como verificado saiu
 *   daqui para o VerifyEmailUseCase. Sem try/catch: link inválido é 400
 *   (InvalidVerificationLinkException) e usuário inexistente 404, no
 *   formato do ExceptionListener — antes o 400 tinha um JSON próprio e
 *   qualquer outro erro virava 500 com a mensagem crua.
 * - id não numérico responde 400 (InputBag::getInt() lança
 *   BadRequestException); id ausente vira 0 e o RequestValidator
 *   responde 400.
 */
#[AsController]
final class VerifyEmailController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Authentication\UseCase\EmailVerification\Verify\VerifyEmailUseCase $verifyEmailUseCase
     */
    public function __construct(
        private readonly VerifyEmailUseCase $verifyEmailUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $response = $this->verifyEmailUseCase->execute(
            new VerifyEmailRequest(
                userId: $request->query->getInt("id"),
                signedUrl: $request->getUri(),
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
