<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Auth\Register\Model;

use App\Application\Authentication\DTO\Register\RegisterUserResponse as RegisterUserResponseDto;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Output model for user registration operations in ApiPlatform
 *
 * This class is automatically mapped from RegisterUserResponse DTO
 * using the AutoMapFromDto attribute - no manual mapper needed!
 */
#[AutoMapFromDto(RegisterUserResponseDto::class)]
final readonly class RegisterUserResponse
{
    /**
     * @param bool     $success
     * @param string   $message
     * @param int|null $userId
     */
    public function __construct(
        public bool $success,
        public string $message,
        public ?int $userId = null,
    ) {}

    /**
     * @param string   $message
     * @param int|null $userId
     *
     * @return self
     */
    public static function success(string $message, ?int $userId = null): self
    {
        return new self(success: true, message: $message, userId: $userId);
    }

    /**
     * @param string $message
     *
     * @return self
     */
    public static function error(string $message): self
    {
        return new self(success: false, message: $message, userId: null);
    }
}
