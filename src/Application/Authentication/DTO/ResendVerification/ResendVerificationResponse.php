<?php

declare(strict_types=1);

namespace App\Application\Authentication\DTO\ResendVerification;

/**
 * Response DTO for email verification resend operations
 *
 * This DTO represents the response from resending email verification.
 * It will be automatically mapped to ApiPlatform Output Models via the auto-mapping system.
 */
final class ResendVerificationResponse
{
    /**
     * @param bool        $success
     * @param string      $message
     * @param bool        $emailSent
     * @param string|null $error
     * @param array       $errors
     */
    public function __construct(
        public private(set) bool $success,
        public private(set) string $message,
        public private(set) bool $emailSent,
        public private(set) ?string $error = null,
        public private(set) array $errors = [],
    ) {}

    /**
     * Create a successful response
     *
     * @param string $message
     * @param bool   $emailSent
     *
     * @return self
     */
    public static function success(
        string $message,
        bool $emailSent = true,
    ): self {
        return new self(
            success: true,
            message: $message,
            emailSent: $emailSent,
        );
    }

    /**
     * Create an error response
     *
     * @param string $message
     * @param string $error
     * @param array  $errors
     *
     * @return self
     */
    public static function error(
        string $message,
        string $error,
        array $errors = [],
    ): self {
        return new self(
            success: false,
            message: $message,
            emailSent: false,
            error: $error,
            errors: $errors,
        );
    }

    /**
     * Check if the response represents a successful operation
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Get response message
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Check if email was sent
     *
     * @return bool
     */
    public function isEmailSent(): bool
    {
        return $this->emailSent;
    }

    /**
     * Get error message
     *
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Get errors array
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Convert response to array format
     *
     * @return array
     */
    public array $asArray {
        get {
            return [
                'success' => $this->success,
                'data' => [
                    'email_sent' => $this->emailSent,
                ],
                'message' => $this->message,
                'error' => $this->error,
                'errors' => $this->errors,
            ];
        }
    }

    /**
     * Legacy toArray method for backwards compatibility
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->asArray;
    }
}
