<?php

declare(strict_types = 1);

namespace App\Domain\Authentication\Exception;

use Throwable;
use RuntimeException;

/**
 *
 */
class InvalidResetTokenException extends RuntimeException {
    public ?string $reason = null {
        get {
            return $this->reason;
        }
    }
    public array $context = [] {
        get {
            return $this->context;
        }
    }

    /**
     * @param string          $message
     * @param int             $code
     * @param \Throwable|null $previous
     * @param string|null     $reason
     * @param array           $context
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?string $reason = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->reason = $reason;
        $this->context = $context;
    }

    /**
     * @param string $token
     *
     * @return self
     */
    public static function invalidFormat(string $token): self {
        return new self(
            message: 'Token format is invalid',
            reason: 'INVALID_FORMAT',
            context: [
                'token_length' => strlen($token),
                'token_prefix' => substr($token, 0, 10),
            ]
        );
    }

    /**
     * @param string $token
     *
     * @return self
     */
    public static function tokenNotFound(string $token): self {
        return new self(
            message: 'Token not found in database',
            reason: 'TOKEN_NOT_FOUND',
            context: [
                'token_prefix' => substr($token, 0, 10),
            ]
        );
    }

    /**
     * @param \Throwable $previous
     *
     * @return self
     */
    public static function bundleError(Throwable $previous): self {
        return new self(
            message: 'Reset password bundle error: '.$previous->getMessage(),
            previous: $previous,
            reason: 'BUNDLE_ERROR',
            context: [
                'original_error' => $previous->getMessage(),
                'error_class' => get_class($previous),
            ]
        );
    }

    /**
     * @param \Throwable $previous
     *
     * @return self
     */
    public static function unexpectedError(Throwable $previous): self {
        return new self(
            message: 'Unexpected error validating token: '.$previous->getMessage(),
            previous: $previous,
            reason: 'UNEXPECTED_ERROR',
            context: [
                'error' => $previous->getMessage(),
                'file' => $previous->getFile(),
                'line' => $previous->getLine(),
            ]
        );
    }
}