<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class ExpiredResetTokenException extends DomainException
{
    public function __construct(
        string $message,
        int $code = 410,
        ?\Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    public function getErrorType(): string
    {
        return 'expired_reset_token_error';
    }

    public static function tokenExpired(string $token, ?\DateTimeInterface $expiredAt = null): self
    {
        $message = 'The password reset token has expired';
        $context = ['token' => substr($token, 0, 8) . '***'];

        if ($expiredAt) {
            $message .= sprintf(' (expired at %s)', $expiredAt->format('Y-m-d H:i:s'));
            $context['expired_at'] = $expiredAt->format('c');
        }

        return new self($message, 410, null, $context);
    }

    public static function tokenTooOld(string $token, int $maxAgeInHours): self
    {
        return new self(
            sprintf(
                'The password reset token is too old. Tokens are valid for %d hours',
                $maxAgeInHours
            ),
            410,
            null,
            [
                'token' => substr($token, 0, 8) . '***',
                'max_age_hours' => $maxAgeInHours,
            ]
        );
    }

    public static function alreadyUsed(string $token): self
    {
        return new self(
            'The password reset token has already been used',
            410,
            null,
            ['token' => substr($token, 0, 8) . '***']
        );
    }

    public static function invalidTokenFormat(string $token): self
    {
        return new self(
            'The password reset token format is invalid',
            400,
            null,
            ['token' => substr($token, 0, 8) . '***']
        );
    }

    public static function tokenRevoked(string $token, ?\DateTimeInterface $revokedAt = null): self
    {
        $message = 'The password reset token has been revoked';
        $context = ['token' => substr($token, 0, 8) . '***'];

        if ($revokedAt) {
            $message .= sprintf(' (revoked at %s)', $revokedAt->format('Y-m-d H:i:s'));
            $context['revoked_at'] = $revokedAt->format('c');
        }

        return new self($message, 410, null, $context);
    }

    public static function maxAttemptsExceeded(string $token, int $maxAttempts): self
    {
        return new self(
            sprintf(
                'Maximum reset attempts exceeded for this token. Maximum allowed: %d attempts',
                $maxAttempts
            ),
            429,
            null,
            [
                'token' => substr($token, 0, 8) . '***',
                'max_attempts' => $maxAttempts,
            ]
        );
    }

    /**
     * @param \DateTimeInterface $expiresAt
     *
     * @return self
     */
    public static function create(\DateTimeInterface $expiresAt): self
    {
        return new self(
            sprintf(
                'Token expired at %s (current time: %s)',
                $expiresAt->format('Y-m-d H:i:s'),
                (new \DateTime())->format('Y-m-d H:i:s')
            ),
            410,
            null,
            ['expired_at' => $expiresAt->format('c')]
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
