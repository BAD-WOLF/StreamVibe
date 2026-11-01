<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class UserNotVerifiedException extends DomainException
{
    public function __construct(
        string $message,
        int $code = 403,
        ?\Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    public function getErrorType(): string
    {
        return 'user_not_verified_error';
    }

    public static function emailNotVerified(int $userId, string $email): self
    {
        return new self(
            sprintf('User %d with email "%s" has not verified their email address', $userId, $email),
            403,
            null,
            [
                'user_id' => $userId,
                'email' => $email,
                'verification_type' => 'email'
            ]
        );
    }

    public static function phoneNotVerified(int $userId, string $phoneNumber): self
    {
        return new self(
            sprintf('User %d with phone number "%s" has not verified their phone number', $userId, $phoneNumber),
            403,
            null,
            [
                'user_id' => $userId,
                'phone_number' => $phoneNumber,
                'verification_type' => 'phone'
            ]
        );
    }

    public static function accountNotVerified(int $userId): self
    {
        return new self(
            sprintf('User %d account is not verified', $userId),
            403,
            null,
            [
                'user_id' => $userId,
                'verification_type' => 'account'
            ]
        );
    }

    public static function verificationPending(int $userId, string $email, ?\DateTimeInterface $sentAt = null): self
    {
        $message = sprintf('Email verification is pending for user %d with email "%s"', $userId, $email);
        $context = [
            'user_id' => $userId,
            'email' => $email,
            'status' => 'verification_pending'
        ];

        if ($sentAt) {
            $message .= sprintf(' (verification email sent at %s)', $sentAt->format('Y-m-d H:i:s'));
            $context['verification_sent_at'] = $sentAt->format('c');
        }

        return new self($message, 403, null, $context);
    }

    public static function verificationExpired(int $userId, string $email, ?\DateTimeInterface $expiredAt = null): self
    {
        $message = sprintf('Email verification has expired for user %d with email "%s"', $userId, $email);
        $context = [
            'user_id' => $userId,
            'email' => $email,
            'status' => 'verification_expired'
        ];

        if ($expiredAt) {
            $message .= sprintf(' (expired at %s)', $expiredAt->format('Y-m-d H:i:s'));
            $context['expired_at'] = $expiredAt->format('c');
        }

        return new self($message, 403, null, $context);
    }

    public static function multipleVerificationsPending(int $userId, array $pendingTypes): self
    {
        return new self(
            sprintf('User %d has multiple pending verifications: %s', $userId, implode(', ', $pendingTypes)),
            403,
            null,
            [
                'user_id' => $userId,
                'pending_verifications' => $pendingTypes,
                'status' => 'multiple_verifications_pending'
            ]
        );
    }

    public static function twoFactorNotVerified(int $userId): self
    {
        return new self(
            sprintf('User %d has not completed two-factor authentication verification', $userId),
            403,
            null,
            [
                'user_id' => $userId,
                'verification_type' => 'two_factor'
            ]
        );
    }

    public static function identityNotVerified(int $userId): self
    {
        return new self(
            sprintf('User %d identity has not been verified', $userId),
            403,
            null,
            [
                'user_id' => $userId,
                'verification_type' => 'identity'
            ]
        );
    }

    public static function documentVerificationRequired(int $userId, array $requiredDocuments): self
    {
        return new self(
            sprintf('User %d must verify the following documents: %s', $userId, implode(', ', $requiredDocuments)),
            403,
            null,
            [
                'user_id' => $userId,
                'required_documents' => $requiredDocuments,
                'verification_type' => 'document'
            ]
        );
    }

    public static function kycNotCompleted(int $userId, string $kycLevel): self
    {
        return new self(
            sprintf('User %d has not completed KYC verification (required level: %s)', $userId, $kycLevel),
            403,
            null,
            [
                'user_id' => $userId,
                'required_kyc_level' => $kycLevel,
                'verification_type' => 'kyc'
            ]
        );
    }

    public static function ageVerificationRequired(int $userId, int $minimumAge): self
    {
        return new self(
            sprintf('User %d must verify they are at least %d years old', $userId, $minimumAge),
            403,
            null,
            [
                'user_id' => $userId,
                'minimum_age' => $minimumAge,
                'verification_type' => 'age'
            ]
        );
    }

    public static function verificationAttemptsExceeded(int $userId, int $maxAttempts): self
    {
        return new self(
            sprintf('User %d has exceeded the maximum verification attempts (%d)', $userId, $maxAttempts),
            429,
            null,
            [
                'user_id' => $userId,
                'max_attempts' => $maxAttempts,
                'status' => 'attempts_exceeded'
            ]
        );
    }

    public static function verificationMethodNotAvailable(int $userId, string $method): self
    {
        return new self(
            sprintf('Verification method "%s" is not available for user %d', $method, $userId),
            422,
            null,
            [
                'user_id' => $userId,
                'verification_method' => $method,
                'status' => 'method_not_available'
            ]
        );
    }

    public static function accountUnderReview(int $userId, string $reviewReason): self
    {
        return new self(
            sprintf('User %d account is under review and cannot be verified at this time: %s', $userId, $reviewReason),
            423,
            null,
            [
                'user_id' => $userId,
                'review_reason' => $reviewReason,
                'status' => 'under_review'
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
