<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Authentication\EmailVerification\ValidateLink;

use App\Application\Authentication\DTO\EmailVerification\ValidateLink\Request\ValidateEmailVerificationLinkRequest;
use App\Application\Authentication\UseCase\EmailVerification\ValidateLink\ValidateEmailVerificationLinkUseCase;
use App\Infrastructure\ApiPlatform\Authentication\EmailVerification\Verify\VerifyEmailEntryPoint;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Controller for checking a verification link without verifying the email
 *
 * GET /{_locale}/api/verify/email/validate?expires=&id=&signature=&token=
 *
 * Recebe os parâmetros do link de verificação (no mesmo locale do link) e
 * confere o link de verificação que eles formam — a URL de
 * VerifyEmailEntryPoint::ROUTE_NAME, que é a que foi assinada. Link
 * inválido ou expirado responde 200 com valid=false (é uma consulta, não
 * uma ação).
 *
 * Só os parâmetros do link (VerifyEmailEntryPoint::LINK_PARAMETERS) entram
 * na URL: um "_locale" na query trocaria o locale da rota gerada.
 *
 * NOTA (out/2026): era EmailVerificationController::validateVerificationToken()
 * (#[Route("/api/verify/email/validate")], fora do prefixo /{_locale}/api).
 * Esse método nunca validava de verdade: a assinatura cobre o endereço
 * do link (/api/verify/email), mas ele conferia a própria requisição
 * (/api/verify/email/validate) — a resposta era sempre valid=false.
 */
#[AsController]
final class ValidateEmailVerificationLinkController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Authentication\UseCase\EmailVerification\ValidateLink\ValidateEmailVerificationLinkUseCase $validateEmailVerificationLinkUseCase
     */
    public function __construct(
        private readonly ValidateEmailVerificationLinkUseCase $validateEmailVerificationLinkUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $linkParameters = array_intersect_key(
            $request->query->all(),
            array_flip(VerifyEmailEntryPoint::LINK_PARAMETERS),
        );

        $response = $this->validateEmailVerificationLinkUseCase->execute(
            new ValidateEmailVerificationLinkRequest(
                userId: $request->query->getInt("id"),
                signedUrl: $this->generateUrl(
                    VerifyEmailEntryPoint::ROUTE_NAME,
                    $linkParameters,
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ),
            ),
        );

        return $this->mapToJsonResponse(dto: $response, status: 200);
    }
}
