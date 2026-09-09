<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Auth\ResendVerification\Model;

use App\Application\Authentication\DTO\ResendVerification\ResendVerificationResponse as ResendVerificationResponseDto;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Output model for email verification resend operations in ApiPlatform
 *
 * This class is automatically mapped from ResendVerificationResponse DTO
 * using the AutoMapFromDto attribute - no manual mapper needed!
 * Error cases are handled by dedicated schemas in the Schema directory.
 */
#[AutoMapFromDto(ResendVerificationResponseDto::class)]
final readonly class ResendVerificationResponse
{
    /**
     * @param bool        $success
     * @param string      $message
     * @param bool        $emailSent
     * @param string|null $error
     */
    public function __construct(
        public bool $success,
        public string $message,
        public bool $emailSent,
        public ?string $error = null,
    ) {}

    /**
     * Create instance from array data
     *
     * @param array $data
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            success: $data["success"] ?? false,
            message: $data["message"] ?? "",
            emailSent: $data["emailSent"] ?? ($data["email_sent"] ?? false),
            error: $data["error"] ?? null,
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
     * Convert to array format
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            "success" => $this->success,
            "message" => $this->message,
            "data" => [
                "email_sent" => $this->emailSent,
            ],
            "error" => $this->error,
        ];
    }
}
