<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Authentication\ResetPassword;

use App\Application\Authentication\UseCase\ResetUserPasswordUseCase;
use App\Domain\Exception\ExpiredResetTokenException;
use App\Domain\Exception\InvalidResetTokenException;
use App\Domain\Exception\ResetTokenNotFoundException;
use App\Domain\Exception\UserValidationException;
use App\Infrastructure\ApiPlatform\Auth\ResetPassword\CheckStatus\CheckStatusEntryPoint;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;

/**
 * Controller for checking password reset token status
 *
 * This controller uses the OutputMappingTrait to automatically map
 * Application DTOs to ApiPlatform Output Models via the auto-mapping system.
 */
#[AsController]
class CheckResetStatusController extends AbstractController
{
    use OutputMappingTrait;

    /**
     * @param \App\Application\Authentication\UseCase\ResetUserPasswordUseCase $resetPasswordUseCase
     * @param \Symfony\Contracts\Translation\TranslatorInterface               $translator
     * @param \Psr\Log\LoggerInterface                                         $logger
     */
    public function __construct(
        private readonly ResetUserPasswordUseCase $resetPasswordUseCase,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Check reset token status
     */
    #[
        Route(
            name: "api_reset_password_status",
            defaults: [
                "_api_resource_class" => CheckStatusEntryPoint::class,
                "_api_operation_name" => "get_reset_password_status",
            ],
            methods: ["GET"],
        ),
    ]
    public function __invoke(string $token): JsonResponse
    {
        $this->logger->info("CheckResetStatusController invoked", [
            "token_received" => !empty($token),
            "token_length" => strlen($token),
            "token_prefix" => substr($token, 0, 10) . "...",
        ]);

        if (empty($token)) {
            $this->logger->warning("Empty token received in controller");
            throw UserValidationException::requiredFieldMissing("token");
        }

        try {
            $this->logger->info("Calling checkStatus use case");

            // Execute use case - exceptions will be handled by ExceptionListener
            $response = $this->resetPasswordUseCase->checkStatus(token: $token);

            $this->logger->info("checkStatus completed successfully", [
                "valid" => $response->valid,
                "expired" => $response->expired,
            ]);

            // Use auto-mapping system to convert DTO to ApiPlatform Output Model
            return $this->safeMapToJsonResponse(dto: $response, status: 200);
        } catch (ResetTokenNotFoundException $e) {
            $this->logger->warning("Reset token not found", [
                "token_prefix" => substr($token, 0, 10) . "...",
                "error" => $e->getMessage(),
            ]);

            return $this->json(
                [
                    "success" => false,
                    "error" => $e->getMessage(),
                    "message" => "Reset token not found",
                ],
                404,
            );
        } catch (ExpiredResetTokenException $e) {
            $this->logger->warning("Reset token expired", [
                "token_prefix" => substr($token, 0, 10) . "...",
                "error" => $e->getMessage(),
            ]);

            return $this->json(
                [
                    "success" => false,
                    "error" => $e->getMessage(),
                    "message" => "Reset token has expired",
                ],
                410,
            );
        } catch (InvalidResetTokenException $e) {
            $this->logger->warning("Invalid reset token", [
                "token_prefix" => substr($token, 0, 10) . "...",
                "error" => $e->getMessage(),
            ]);

            return $this->json(
                [
                    "success" => false,
                    "error" => $e->getMessage(),
                    "message" => "Invalid reset token",
                ],
                400,
            );
        } catch (Exception $e) {
            $this->logger->error(
                "Unexpected error checking reset token status",
                [
                    "token_prefix" => substr($token, 0, 10) . "...",
                    "error" => $e->getMessage(),
                    "trace" => $e->getTraceAsString(),
                ],
            );

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
