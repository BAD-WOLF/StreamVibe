<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Authentication\ResetPassword;

use App\Application\Authentication\DTO\Reset\Request\ResetPasswordRequest;
use App\Application\Authentication\UseCase\ResetUserPasswordUseCase;
use App\Domain\Exception\EmailDeliveryException;
use App\Domain\Exception\RateLimitExceededException;
use App\Domain\Exception\ResetPasswordValidationException;
use App\Domain\Exception\UserNotFoundException;
use App\Domain\Exception\UserNotVerifiedException;
use App\Domain\Exception\UserValidationException;
use App\Infrastructure\ApiPlatform\Auth\ResetPassword\Solicitation\SolicitationEntryPoint;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;

/**
 * Controller for password reset solicitation requests
 *
 * This controller uses the OutputMappingTrait to automatically map
 * Application DTOs to ApiPlatform Output Models via the auto-mapping system.
 *
 * NOTA (bug crítico, ago/2026): este controller chamava
 * $this->resetPasswordUseCase->requestReset(...), método que nunca existiu
 * em ResetUserPasswordUseCase (métodos reais: execute(), checkStatus(),
 * cancel(), getUserActiveRequests()). Toda requisição de solicitação de
 * reset de senha em produção quebrava com "Call to undefined method",
 * capturada pelo catch (Exception) genérico e reportada como 500
 * internal_error — sem NENHUM teste de API cobrindo esse endpoint pra
 * pegar isso (só existia teste unitário do UseCase isolado, que nunca
 * passa pelo controller). Corrigido para chamar execute(), que é o método
 * real e recebe exatamente o mesmo ResetPasswordRequest já construído
 * aqui embaixo.
 */
#[AsController]
class ResetPasswordSolicitationController extends AbstractController
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
     * Request password reset
     */
    #[
        Route(
            name: "api_reset_password_solicitation",
            defaults: [
                "_api_resource_class" => SolicitationEntryPoint::class,
                "_api_operation_name" => "post_reset_password_solicitation",
            ],
            methods: ["POST"],
        ),
    ]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), associative: true);

        if (!is_array($data)) {
            throw UserValidationException::invalidFieldValue(
                "request_body",
                $data,
                "array",
            );
        }

        if (empty($data["email"])) {
            throw UserValidationException::requiredFieldMissing("email");
        }

        // Create request DTO
        $resetRequest = new ResetPasswordRequest(
            email: $data["email"],
            userAgent: $request->headers->get("User-Agent", ""),
            ipAddress: $request->getClientIp() ?? "",
        );

        try {
            // Execute use case - exceptions will be handled by the ExceptionListener
            $response = $this->resetPasswordUseCase->execute(
                request: $resetRequest,
            );

            // Use auto-mapping system to convert DTO to ApiPlatform Output Model
            return $this->safeMapToJsonResponse(dto: $response, status: 200);
        } catch (UserNotFoundException $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $e->getMessage(),
                    "message" => "User not found with the provided email",
                ],
                404,
            );
        } catch (UserNotVerifiedException $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $e->getMessage(),
                    "message" => "User account is not verified",
                ],
                403,
            );
        } catch (RateLimitExceededException $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $e->getMessage(),
                    "message" =>
                        "Too many reset requests. Please try again later.",
                ],
                429,
            );
        } catch (ResetPasswordValidationException $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $e->getMessage(),
                    "message" => "Validation failed for password reset request",
                ],
                422,
            );
        } catch (EmailDeliveryException $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $e->getMessage(),
                    "message" => "Failed to send reset password email",
                ],
                500,
            );
        } catch (Exception $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $this->translator->trans(
                        "An unexpected error occurred",
                    ),
                    "message" => $e->getMessage(),
                ],
                500,
            );
        }
    }
}

