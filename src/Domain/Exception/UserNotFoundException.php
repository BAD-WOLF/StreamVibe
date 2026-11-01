<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class UserNotFoundException extends DomainException
{
    public function __construct(
        string $message,
        int $code = 404,
        ?\Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    public function getErrorType(): string
    {
        return 'user_not_found_error';
    }

    public static function withId(int $userId): self
    {
        return new self(
            sprintf('User with ID %d not found', $userId),
            404,
            null,
            ['user_id' => $userId]
        );
    }

    public static function withEmail(string $email): self
    {
        return new self(
            sprintf('User with email "%s" not found', $email),
            404,
            null,
            ['email' => $email]
        );
    }

    public static function withUsername(string $username): self
    {
        return new self(
            sprintf('User with username "%s" not found', $username),
            404,
            null,
            ['username' => $username]
        );
    }

    public static function withPhoneNumber(string $phoneNumber): self
    {
        return new self(
            sprintf('User with phone number "%s" not found', $phoneNumber),
            404,
            null,
            ['phone_number' => $phoneNumber]
        );
    }

    public static function withExternalId(string $provider, string $externalId): self
    {
        return new self(
            sprintf('User with %s ID "%s" not found', $provider, $externalId),
            404,
            null,
            [
                'provider' => $provider,
                'external_id' => $externalId
            ]
        );
    }

    public static function withToken(string $token): self
    {
        return new self(
            'User associated with the provided token not found',
            404,
            null,
            ['token' => substr($token, 0, 8) . '***']
        );
    }

    public static function withResetToken(string $resetToken): self
    {
        return new self(
            'User associated with the password reset token not found',
            404,
            null,
            ['reset_token' => substr($resetToken, 0, 8) . '***']
        );
    }

    public static function withVerificationToken(string $verificationToken): self
    {
        return new self(
            'User associated with the verification token not found',
            404,
            null,
            ['verification_token' => substr($verificationToken, 0, 8) . '***']
        );
    }

    public static function withApiKey(string $apiKey): self
    {
        return new self(
            'User associated with the API key not found',
            404,
            null,
            ['api_key' => substr($apiKey, 0, 8) . '***']
        );
    }

    public static function deletedUser(int $userId): self
    {
        return new self(
            sprintf('User with ID %d has been deleted', $userId),
            404,
            null,
            [
                'user_id' => $userId,
                'status' => 'deleted'
            ]
        );
    }

    public static function suspendedUser(int $userId): self
    {
        return new self(
            sprintf('User with ID %d is suspended', $userId),
            403,
            null,
            [
                'user_id' => $userId,
                'status' => 'suspended'
            ]
        );
    }

    public static function inactiveUser(int $userId): self
    {
        return new self(
            sprintf('User with ID %d is inactive', $userId),
            403,
            null,
            [
                'user_id' => $userId,
                'status' => 'inactive'
            ]
        );
    }

    public static function unverifiedUser(int $userId): self
    {
        return new self(
            sprintf('User with ID %d is not verified', $userId),
            403,
            null,
            [
                'user_id' => $userId,
                'status' => 'unverified'
            ]
        );
    }

    public static function byMultipleCriteria(array $criteria): self
    {
        $criteriaStr = [];
        foreach ($criteria as $key => $value) {
            if (is_string($value) && in_array($key, ['token', 'api_key', 'reset_token'])) {
                $criteriaStr[] = sprintf('%s: %s***', $key, substr($value, 0, 8));
            } else {
                $criteriaStr[] = sprintf('%s: %s', $key, $value);
            }
        }

        return new self(
            sprintf('User not found with criteria: %s', implode(', ', $criteriaStr)),
            404,
            null,
            ['search_criteria' => $criteria]
        );
    }

    public static function noUsersFound(): self
    {
        return new self(
            'No users found matching the specified criteria',
            404,
            null,
            ['scope' => 'multiple_users']
        );
    }

    public static function userNotFoundInRole(int $userId, string $role): self
    {
        return new self(
            sprintf('User with ID %d not found with role "%s"', $userId, $role),
            404,
            null,
            [
                'user_id' => $userId,
                'role' => $role
            ]
        );
    }

    public static function userNotFoundInGroup(int $userId, string $group): self
    {
        return new self(
            sprintf('User with ID %d not found in group "%s"', $userId, $group),
            404,
            null,
            [
                'user_id' => $userId,
                'group' => $group
            ]
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->getErrorType(),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->context,
        ];
    }
}
