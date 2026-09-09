<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class UserAlreadyExistsException extends DomainException
{
    public function __construct(
        string $message,
        int $code = 409,
        ?\Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    public function getErrorType(): string
    {
        return 'user_already_exists_error';
    }

    public static function withEmail(string $email): self
    {
        return new self(
            sprintf('A user with email "%s" already exists', $email),
            409,
            null,
            ['email' => $email]
        );
    }

    public static function withUsername(string $username): self
    {
        return new self(
            sprintf('A user with username "%s" already exists', $username),
            409,
            null,
            ['username' => $username]
        );
    }

    public static function withEmailAndUsername(string $email, string $username): self
    {
        return new self(
            sprintf('A user with email "%s" or username "%s" already exists', $email, $username),
            409,
            null,
            [
                'email' => $email,
                'username' => $username
            ]
        );
    }

    public static function withPhoneNumber(string $phoneNumber): self
    {
        return new self(
            sprintf('A user with phone number "%s" already exists', $phoneNumber),
            409,
            null,
            ['phone_number' => $phoneNumber]
        );
    }

    public static function withExternalId(string $provider, string $externalId): self
    {
        return new self(
            sprintf('A user with %s ID "%s" already exists', $provider, $externalId),
            409,
            null,
            [
                'provider' => $provider,
                'external_id' => $externalId
            ]
        );
    }

    public static function duplicateRegistrationAttempt(string $email, int $userId): self
    {
        return new self(
            sprintf('User with email "%s" is already registered (User ID: %d)', $email, $userId),
            409,
            null,
            [
                'email' => $email,
                'existing_user_id' => $userId,
                'action' => 'registration_attempt'
            ]
        );
    }

    public static function accountPendingVerification(string $email): self
    {
        return new self(
            sprintf('An account with email "%s" exists but is pending email verification', $email),
            409,
            null,
            [
                'email' => $email,
                'status' => 'pending_verification'
            ]
        );
    }

    public static function softDeletedUserExists(string $email, ?\DateTimeInterface $deletedAt = null): self
    {
        $message = sprintf('A deleted user with email "%s" already exists', $email);
        $context = [
            'email' => $email,
            'status' => 'soft_deleted'
        ];

        if ($deletedAt) {
            $message .= sprintf(' (deleted at %s)', $deletedAt->format('Y-m-d H:i:s'));
            $context['deleted_at'] = $deletedAt->format('c');
        }

        return new self($message, 409, null, $context);
    }

    public static function suspendedUserExists(string $email): self
    {
        return new self(
            sprintf('A suspended user with email "%s" already exists', $email),
            409,
            null,
            [
                'email' => $email,
                'status' => 'suspended'
            ]
        );
    }

    public static function multipleFieldsConflict(array $conflicts): self
    {
        $fields = array_keys($conflicts);
        $message = sprintf('User already exists with conflicting %s', implode(', ', $fields));

        return new self(
            $message,
            409,
            null,
            [
                'conflicts' => $conflicts,
                'conflicting_fields' => $fields
            ]
        );
    }

    public static function concurrentRegistration(string $email): self
    {
        return new self(
            sprintf('Concurrent registration detected for email "%s"', $email),
            409,
            null,
            [
                'email' => $email,
                'reason' => 'concurrent_registration'
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
