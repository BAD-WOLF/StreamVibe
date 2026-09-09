<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Auth\ResetPassword\CheckStatus\Model;

use App\Application\Authentication\DTO\Reset\Response\ResetPasswordStatusResponse as ResetPasswordStatusResponseDto;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Output model for check reset password status operations in ApiPlatform
 *
 * This class is automatically mapped from ResetPasswordStatusResponse DTO
 * using the AutoMapFromDto attribute - no manual mapper needed!
 * Error cases are handled by dedicated schemas in the Schema directory.
 */
#[AutoMapFromDto(ResetPasswordStatusResponseDto::class)]
final readonly class CheckResetStatusOutput
{
    /**
     * @param bool        $success
     * @param array       $data
     * @param string|null $message
     */
    public function __construct(
        public bool $success,
        public array $data,
        public ?string $message = null,
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
            success: $data["success"] ?? true,
            data: $data["data"] ?? [],
            message: $data["message"] ?? null,
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
     * Get response data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get response message
     *
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
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
            "data" => $this->data,
            "message" => $this->message,
        ];
    }
}
