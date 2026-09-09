<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Authentication\ResetPassword;

use App\Application\Authentication\DTO\Reset\Request\CancelResetRequest;
use App\Application\Authentication\UseCase\ResetUserPasswordUseCase;
use App\Domain\Exception\ResetTokenNotFoundException;
use App\Domain\Exception\UserValidationException;
use App\Infrastructure\ApiPlatform\Auth\ResetPassword\Cancel\CancelResetEntryPoint;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;
use RuntimeException;

/**
 * Controller for canceling password reset requests
 *
 * This controller uses the OutputMappingTrait to automatically map
 * Application DTOs to ApiPlatform Output Models via the auto-mapping system.
 */
#[AsController]
class CancelResetPasswordController extends AbstractController
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
     * Cancel reset password request
     */
    #[
        Route(
            name: "api_reset_password_cancel",
            defaults: [
                "_api_resource_class" => CancelResetEntryPoint::class,
                "_api_operation_name" => "post_cancel_reset_password",
            ],
            methods: ["POST"],
        ),
    ]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode(json: $request->getContent(), associative: true);

        if (!is_array(value: $data)) {
            throw UserValidationException::invalidFieldValue(
                "request_body",
                $data,
                "array",
            );
        }

        if (empty($data["token"])) {
            throw UserValidationException::requiredFieldMissing("token");
        }

        try {
            $cancelRequest = new CancelResetRequest(
                token: $data["token"],
                userAgent: $request->headers->get(
                    key: "User-Agent",
                    default: "",
                ),
                ipAddress: $request->getClientIp() ?? "",
            );

            // Execute use case - exceptions will be handled by the ExceptionListener
            $response = $this->resetPasswordUseCase->cancel(
                token: $cancelRequest->token,
            );

            // Use auto-mapping system to convert DTO to ApiPlatform Output Model
            return $this->safeMapToJsonResponse(dto: $response, status: 200);
        } catch (ResetTokenNotFoundException $e) {
            return $this->json(
                data: [
                    "success" => false,
                    "error" => $e->getMessage(),
                    "message" => "Reset token not found",
                ],
                status: 404,
            );
        } catch (RuntimeException $e) {
            return $this->json(
                data: [
                    "success" => false,
                    "error" => $this->translator->trans(
                        id: "An error occurred while canceling reset request",
                    ),
                    "message" => $e->getMessage(),
                ],
                status: 500,
            );
        } catch (Exception $e) {
            return $this->json(
                data: [
                    "success" => false,
                    "error" => $this->translator->trans(
                        id: "An unexpected error occurred",
                    ),
                    "message" => $e->getMessage(),
                ],
                status: 500,
            );
        }
    }
}
