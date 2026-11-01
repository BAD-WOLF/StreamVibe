<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Authentication\EmailVerification\Status;

use App\Application\Authentication\DTO\EmailVerification\Status\Request\GetEmailVerificationStatusRequest;
use App\Application\Authentication\UseCase\EmailVerification\Status\GetEmailVerificationStatusUseCase;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for checking whether an account already verified its email
 *
 * GET /{_locale}/api/verify/email/status/{userId}
 *
 * NOTA (out/2026): era EmailVerificationController::checkVerificationStatus()
 * (#[Route("/api/verify/email/status/{id}")], fora do prefixo
 * /{_locale}/api). A rota é pública, e a resposta trazia o EMAIL do
 * usuário: percorrendo os ids, qualquer pessoa coletava os emails
 * cadastrados. Agora devolve só user_id e verified (ver
 * GetEmailVerificationStatusResponse). Usuário inexistente é 404 pelo
 * ExceptionListener — antes, o catch (Exception) do controller
 * transformava o próprio UserNotFoundException em 500.
 */
#[AsController]
final class GetEmailVerificationStatusController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Authentication\UseCase\EmailVerification\Status\GetEmailVerificationStatusUseCase $getEmailVerificationStatusUseCase
     */
    public function __construct(
        private readonly GetEmailVerificationStatusUseCase $getEmailVerificationStatusUseCase,
    ) {}

    public function __invoke(int $userId): JsonResponse
    {
        $response = $this->getEmailVerificationStatusUseCase->execute(
            new GetEmailVerificationStatusRequest(
                userId: $userId,
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
