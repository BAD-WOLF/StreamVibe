<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class ResetTokenNotFoundException extends DomainException
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
        return 'reset_token_not_found_error';
    }

    public static function tokenNotFound(string $token): self
    {
        return new self(
            'Password reset token not found',
            404,
            null,
            ['token' => substr($token, 0, 8) . '***']
        );
    }

    public static function tokenNotFoundForUser(int $userId): self
    {
        return new self(
            sprintf('No active password reset token found for user %d', $userId),
            404,
            null,
            ['user_id' => $userId]
        );
    }

    public static function tokenNotFoundForEmail(string $email): self
    {
        return new self(
            sprintf('No active password reset token found for email "%s"', $email),
            404,
            null,
            ['email' => $email]
        );
    }

    public static function noActiveTokens(): self
    {
        return new self(
            'No active password reset tokens found',
            404,
            null,
            ['scope' => 'all_tokens']
        );
    }

    public static function tokenDeletedOrRevoked(string $token): self
    {
        return new self(
            'Password reset token has been deleted or revoked',
            404,
            null,
            [
                'token' => substr($token, 0, 8) . '***',
                'status' => 'deleted_or_revoked'
            ]
        );
    }

    public static function tokenNotFoundInDatabase(string $token): self
    {
        return new self(
            'Password reset token does not exist in database',
            404,
            null,
            [
                'token' => substr($token, 0, 8) . '***',
                'location' => 'database'
            ]
        );
    }

    public static function tokenNotFoundInCache(string $token): self
    {
        return new self(
            'Password reset token not found in cache',
            404,
            null,
            [
                'token' => substr($token, 0, 8) . '***',
                'location' => 'cache'
            ]
        );
    }

    public static function userHasNoTokens(int $userId): self
    {
        return new self(
            sprintf('User %d has no password reset tokens', $userId),
            404,
            null,
            [
                'user_id' => $userId,
                'token_count' => 0
            ]
        );
    }

    public static function allTokensExpired(int $userId, int $expiredCount): self
    {
        return new self(
            sprintf('All password reset tokens for user %d have expired (%d tokens)', $userId, $expiredCount),
            404,
            null,
            [
                'user_id' => $userId,
                'expired_count' => $expiredCount,
                'status' => 'all_expired'
            ]
        );
    }

    public static function tokenNotFoundForSelector(string $selector): self
    {
        return new self(
            'Password reset token not found for selector',
            404,
            null,
            ['selector' => substr($selector, 0, 8) . '***']
        );
    }

    public static function invalidTokenQuery(string $queryType): self
    {
        return new self(
            sprintf('Invalid token query type: %s', $queryType),
            400,
            null,
            ['query_type' => $queryType]
        );
    }

    /**
     * @param string $token
     *
     * @return self
     */
    public static function byToken(string $token): self
    {
        return new self(
            'No reset request found for provided token',
            404,
            null,
            [
                'token' => substr($token, 0, 8) . '***',
                'token_length' => strlen($token),
            ]
        );
    }

    /**
     * @param int $userId
     *
     * @return self
     */
    public static function byUser(int $userId): self
    {
        return new self(
            sprintf('No reset request found for user %d', $userId),
            404,
            null,
            ['user_id' => $userId]
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
