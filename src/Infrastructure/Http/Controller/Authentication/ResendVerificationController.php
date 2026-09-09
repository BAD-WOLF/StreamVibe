<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Authentication;

use App\Application\Authentication\DTO\Register\RegisterUserRequest;
use App\Application\Authentication\UseCase\RegisterUserUseCase;
use App\Domain\Exception\UserValidationException;
use App\Domain\Exception\EmailDeliveryException;
use App\Infrastructure\ApiPlatform\Auth\ResendVerification\ResendVerificationEntryPoint;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Controller for resending verification emails
 *
 * This controller uses the OutputMappingTrait to automatically map
 * Application DTOs to ApiPlatform Output Models via the auto-mapping system.
 */
#[AsController]
class ResendVerificationController extends AbstractController
{
    use OutputMappingTrait;
    public function __construct(
        private RegisterUserUseCase $registerUserUseCase,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
    ) {}

    /**
     * Resend verification email
     */
    #[
        Route(
            name: "api_resend_verification",
            defaults: [
                "_api_resource_class" => ResendVerificationEntryPoint::class,
                "_api_operation_name" => "post_resend_verification",
            ],
            methods: ["POST"],
        ),
    ]
    public function __invoke(Request $request): JsonResponse
    {
        $this->logger->info("=== RESEND VERIFICATION REQUEST STARTED ===");

        $data = json_decode(json: $request->getContent(), associative: true);

        if (!is_array(value: $data)) {
            $this->logger->error("VALIDATION FAILED: Invalid JSON data", [
                "content" => $request->getContent(),
            ]);

            throw UserValidationException::invalidFieldValue(
                "request_body",
                $data,
                "array",
            );
        }

        if (!isset($data["email"])) {
            $this->logger->error("VALIDATION FAILED: Email is missing", [
                "received_data" => array_keys($data),
            ]);

            throw UserValidationException::requiredFieldMissing("email");
        }

        $email = trim($data["email"]);

        if (empty($email)) {
            $this->logger->error("VALIDATION FAILED: Email is empty");

            throw UserValidationException::requiredFieldMissing("email");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->logger->error("VALIDATION FAILED: Invalid email format", [
                "email" => $email,
            ]);

            throw UserValidationException::invalidEmail($email);
        }

        $this->logger->info("STEP 1: Validation passed", [
            "email" => $email,
        ]);

        $this->logger->info("STEP 2: Creating RegisterUserRequest for resend");

        $registerRequest = new RegisterUserRequest(
            email: $email,
            password: "", // Not needed for resend
            agreeTerms: true, // Not validated for resend
            resendVerification: true,
        );

        $this->logger->info("STEP 3: Executing RegisterUserUseCase");

        // Execute use case - exceptions will be handled by ExceptionListener
        $response = $this->registerUserUseCase->execute(
            request: $registerRequest,
        );

        $this->logger->info(
            "=== RESEND VERIFICATION COMPLETED SUCCESSFULLY ===",
            [
                "email" => $email,
                "email_sent" => $response->emailSent,
            ],
        );

        // Use auto-mapping system to convert DTO to ApiPlatform Output Model
        return $this->safeMapToJsonResponse(dto: $response, status: 200);
    }
}
