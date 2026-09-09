<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class InvalidResetTokenException extends DomainException
{
    public function __construct(
            string $message,
            int $code = 400,
            ?\Exception $previous = null,
            array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    public function getErrorType(): string
    {
        return 'invalid_reset_token_error';
    }

    public static function tokenNotFound(string $token): self
    {
        return new self(
                'The password reset token was not found',
                404,
                null,
                ['token' => substr($token, 0, 8) . '***']
        );
    }

    public static function malformedToken(string $token): self
    {
        return new self(
                'The password reset token is malformed or corrupted',
                400,
                null,
                ['token' => substr($token, 0, 8) . '***']
        );
    }

    public static function invalidSignature(string $token): self
    {
        return new self(
                'The password reset token has an invalid signature',
                400,
                null,
                ['token' => substr($token, 0, 8) . '***']
        );
    }

    public static function tokenTooShort(string $token, int $minLength): self
    {
        return new self(
                sprintf(
                        'The password reset token is too short. Minimum length: %d characters',
                        $minLength
                ),
                400,
                null,
                [
                        'token' => substr($token, 0, 8) . '***',
                        'min_length' => $minLength,
                        'actual_length' => strlen($token),
                ]
        );
    }

    public static function invalidCharacters(string $token): self
    {
        return new self(
                'The password reset token contains invalid characters',
                400,
                null,
                ['token' => substr($token, 0, 8) . '***']
        );
    }

    public static function userMismatch(string $token, int $expectedUserId, int $actualUserId): self
    {
        return new self(
                'The password reset token does not belong to the specified user',
                403,
                null,
                [
                        'token' => substr($token, 0, 8) . '***',
                        'expected_user_id' => $expectedUserId,
                        'actual_user_id' => $actualUserId,
                ]
        );
    }

    public static function invalidTokenType(string $token, string $expectedType, string $actualType): self
    {
        return new self(
                sprintf(
                        'Invalid token type. Expected "%s", got "%s"',
                        $expectedType,
                        $actualType
                ),
                400,
                null,
                [
                        'token' => substr($token, 0, 8) . '***',
                        'expected_type' => $expectedType,
                        'actual_type' => $actualType,
                ]
        );
    }

    public static function tokenFromDifferentSource(string $token, string $expectedSource, string $actualSource): self
    {
        return new self(
                sprintf(
                        'Token issued from different source. Expected "%s", got "%s"',
                        $expectedSource,
                        $actualSource
                ),
                400,
                null,
                [
                        'token' => substr($token, 0, 8) . '***',
                        'expected_source' => $expectedSource,
                        'actual_source' => $actualSource,
                ]
        );
    }

    public static function emptyToken(): self
    {
        return new self(
                'The password reset token cannot be empty',
                400,
                null,
                ['token' => 'empty']
        );
    }

    public static function invalidEncoding(string $token): self
    {
        return new self(
                'The password reset token has invalid encoding',
                400,
                null,
                ['token' => substr($token, 0, 8) . '***']
        );
    }

    /**
     * @param string $token
     *
     * @return self
     */
    public static function invalidFormat(string $token): self
    {
        return new self(
                'The password reset token format is invalid',
                400,
                null,
                [
                        'token' => substr($token, 0, 8) . '***',
                        'token_length' => strlen($token),
                ]
        );
    }

    /**
     * @param \Exception $previous
     *
     * @return self
     */
    public static function bundleError(\Exception $previous): self
    {
        return new self(
                'Reset password bundle error: ' . $previous->getMessage(),
                400,
                $previous,
                [
                        'original_error' => $previous->getMessage(),
                        'error_class' => get_class($previous),
                ]
        );
    }

    /**
     * @param \Exception $previous
     *
     * @return self
     */
    public static function unexpectedError(\Exception $previous): self
    {
        return new self(
                'Unexpected error validating token: ' . $previous->getMessage(),
                400,
                $previous,
                [
                        'error' => $previous->getMessage(),
                        'file' => $previous->getFile(),
                        'line' => $previous->getLine(),
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