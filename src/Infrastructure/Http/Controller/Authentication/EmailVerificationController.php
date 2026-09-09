<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Authentication;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Exception\UserNotFoundException;
use App\Domain\Exception\UserValidationException;
use App\Domain\Exception\UserNotVerifiedException;
use App\Domain\Exception\EmailDeliveryException;
use App\Infrastructure\Security\EmailVerifier;
use App\Infrastructure\ApiPlatform\Shared\Trait\OutputMappingTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Exception;

/**
 * Controller for email verification operations
 *
 * This controller uses the OutputMappingTrait to automatically map
 * Application DTOs to ApiPlatform Output Models via the auto-mapping system.
 */
#[AsController]
class EmailVerificationController extends AbstractController
{
    use OutputMappingTrait;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EmailVerifier $emailVerifier,
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * Verify user email address
     */
    #[
        Route(
            path: "/api/verify/email",
            name: "api_verify_email",
            methods: ["GET"],
        ),
    ]
    public function verifyUserEmail(Request $request): JsonResponse
    {
        $id = $request->query->get("id");

        if (!$id) {
            throw UserValidationException::requiredFieldMissing("id");
        }

        if (!is_numeric($id)) {
            throw UserValidationException::invalidFieldValue(
                "id",
                $id,
                "integer",
            );
        }

        try {
            $user = $this->userRepository->findById((int) $id);

            if (!$user) {
                throw UserNotFoundException::withId((int) $id);
            }

            if ($user->isVerified()) {
                return $this->json([
                    "success" => true,
                    "message" => $this->translator->trans(
                        "Email is already verified",
                    ),
                    "data" => [
                        "user_id" => $user->id,
                        "already_verified" => true,
                        "verified" => true,
                    ],
                ]);
            }

            $this->emailVerifier->handleEmailConfirmation($request, $user);

            // TODO: Create EmailVerificationResponse DTO and use auto-mapping
            // For now, return structured data directly
            return $this->json([
                "success" => true,
                "message" => $this->translator->trans(
                    "Email verified successfully",
                ),
                "data" => [
                    "user_id" => $user->id,
                    "verified" => true,
                    "already_verified" => false,
                ],
            ]);
        } catch (VerifyEmailExceptionInterface $exception) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $this->translator->trans(
                        "Invalid or expired verification token",
                    ),
                    "message" => $exception->getReason(),
                    "data" => [
                        "reason" => $exception->getReason(),
                        "user_id" => (int) $id,
                    ],
                ],
                400,
            );
        } catch (Exception $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $this->translator->trans(
                        "An error occurred during email verification",
                    ),
                    "message" => $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Check email verification status
     */
    #[
        Route(
            path: "/api/verify/email/status/{id}",
            name: "api_verify_email_status",
            requirements: ["id" => "\d+"],
            methods: ["GET"],
        ),
    ]
    public function checkVerificationStatus(int $id): JsonResponse
    {
        try {
            $user = $this->userRepository->findById($id);

            if (!$user) {
                throw UserNotFoundException::withId($id);
            }

            // TODO: Create EmailVerificationStatusResponse DTO and use auto-mapping
            // For now, return structured data directly
            return $this->json([
                "success" => true,
                "data" => [
                    "user_id" => $user->id,
                    "email" => $user->getEmail(),
                    "is_verified" => $user->isVerified(),
                    "verification_required" => !$user->isVerified(),
                ],
                "message" => $user->isVerified()
                    ? "User email is verified"
                    : "User email verification required",
            ]);
        } catch (Exception $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $this->translator->trans(
                        "An error occurred while checking verification status",
                    ),
                    "message" => $e->getMessage(),
                    "user_id" => $id,
                ],
                500,
            );
        }
    }

    /**
     * Resend verification email
     */
    #[
        Route(
            path: "/api/verify/email/resend",
            name: "api_resend_verification_email",
            methods: ["POST"],
        ),
    ]
    public function resendVerificationEmail(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || empty($data["email"])) {
            throw UserValidationException::requiredFieldMissing("email");
        }

        $email = trim($data["email"]);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw UserValidationException::invalidFieldValue(
                "email",
                $email,
                "valid email address",
            );
        }

        try {
            $user = $this->userRepository->findByEmail($email);

            if (!$user) {
                // For security reasons, don't reveal if email exists or not
                return $this->json([
                    "success" => true,
                    "message" => $this->translator->trans(
                        "If an account with this email exists and is not verified, a verification email has been sent",
                    ),
                    "data" => [
                        "email_sent" => true,
                    ],
                ]);
            }

            if ($user->isVerified()) {
                return $this->json([
                    "success" => true,
                    "message" => $this->translator->trans(
                        "Email is already verified",
                    ),
                    "data" => [
                        "already_verified" => true,
                        "email_sent" => false,
                    ],
                ]);
            }

            // Generate new verification signature
            $verificationData = $this->emailVerifier->generateConfirmationSignature(
                "api_verify_email",
                $user,
            );

            // TODO: Create ResendVerificationEmailResponse DTO and use auto-mapping
            // For now, return structured data directly
            return $this->json([
                "success" => true,
                "message" => $this->translator->trans(
                    "Verification email has been sent",
                ),
                "data" => [
                    "verification_url" => $verificationData["signedUrl"],
                    "email" => $email,
                    "email_sent" => true,
                ],
            ]);
        } catch (EmailDeliveryException $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $e->getMessage(),
                    "message" => "Failed to send verification email",
                    "data" => [
                        "email" => $email,
                        "email_sent" => false,
                    ],
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

    /**
     * Validate verification token without completing verification
     */
    #[
        Route(
            path: "/api/verify/email/validate",
            name: "api_validate_verification_token",
            methods: ["GET"],
        ),
    ]
    public function validateVerificationToken(Request $request): JsonResponse
    {
        $id = $request->query->get("id");

        if (!$id || !is_numeric($id)) {
            throw UserValidationException::invalidFieldValue(
                "id",
                $id,
                "valid integer",
            );
        }

        try {
            $user = $this->userRepository->findById((int) $id);

            if (!$user) {
                throw UserNotFoundException::withId((int) $id);
            }

            if ($user->isVerified()) {
                return $this->json([
                    "success" => true,
                    "data" => [
                        "valid" => true,
                        "message" => $this->translator->trans(
                            "Email is already verified",
                        ),
                        "already_verified" => true,
                        "user_id" => $user->id,
                    ],
                ]);
            }

            // Validate token without marking as verified
            $isValid = $this->emailVerifier->validateEmailConfirmation(
                $request,
                $user,
            );

            // TODO: Create TokenValidationResponse DTO and use auto-mapping
            // For now, return structured data directly
            return $this->json([
                "success" => true,
                "data" => [
                    "valid" => $isValid,
                    "message" => $isValid
                        ? $this->translator->trans(
                            "Verification token is valid",
                        )
                        : $this->translator->trans(
                            "Verification token is invalid or expired",
                        ),
                    "user_id" => $user->id,
                    "already_verified" => false,
                ],
            ]);
        } catch (Exception $e) {
            return $this->json(
                [
                    "success" => false,
                    "error" => $this->translator->trans(
                        "An error occurred while validating verification token",
                    ),
                    "message" => $e->getMessage(),
                ],
                500,
            );
        }
    }
}
