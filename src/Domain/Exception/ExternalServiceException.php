<?php

declare(strict_types = 1);

namespace App\Domain\Exception;

use Exception;

/**
 *
 */
class ExternalServiceException extends DomainException {
    /**
     * @param string          $message
     * @param int             $code
     * @param \Exception|null $previous
     * @param array           $context
     */
    public function __construct(
        string $message,
        int $code = 502,
        ?Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    /**
     * @return string
     */
    public function getErrorType(): string {
        return 'external_service_error';
    }

    /**
     * @param string $service
     * @param string $error
     * @param array  $context
     *
     * @return self
     */
    public static function clientError(string $service, string $error, array $context = []): self {
        $message = sprintf('Client error from %s: %s', $service, $error);

        return new self(
            $message, 400, null, array_merge($context, [
            'service' => $service,
            'error_type' => 'client_error',
            'original_error' => $error,
        ])
        );
    }

    /**
     * @param string $service
     * @param string $error
     * @param array  $context
     *
     * @return self
     */
    public static function serverError(string $service, string $error, array $context = []): self {
        $message = sprintf('Server error from %s: %s', $service, $error);

        return new self(
            $message, 502, null, array_merge($context, [
            'service' => $service,
            'error_type' => 'server_error',
            'original_error' => $error,
        ])
        );
    }

    /**
     * @param string $service
     * @param array  $context
     *
     * @return self
     */
    public static function timeout(string $service, array $context = []): self {
        $message = sprintf('Timeout error from %s', $service);

        return new self(
            $message, 504, null, array_merge($context, [
            'service' => $service,
            'error_type' => 'timeout',
        ])
        );
    }

    /**
     * @param string $service
     * @param string $reason
     * @param array  $context
     *
     * @return self
     */
    public static function connectionFailed(string $service, string $reason = '', array $context = []): self {
        $message = sprintf('Connection failed to %s', $service);
        if ($reason) {
            $message .= sprintf(': %s', $reason);
        }

        return new self(
            $message, 503, null, array_merge($context, [
            'service' => $service,
            'error_type' => 'connection_failed',
            'reason' => $reason,
        ])
        );
    }

    /**
     * @param string $service
     * @param string $reason
     * @param array  $context
     *
     * @return self
     */
    public static function invalidResponse(string $service, string $reason = '', array $context = []): self {
        $message = sprintf('Invalid response from %s', $service);
        if ($reason) {
            $message .= sprintf(': %s', $reason);
        }

        return new self(
            $message, 502, null, array_merge($context, [
            'service' => $service,
            'error_type' => 'invalid_response',
            'reason' => $reason,
        ])
        );
    }

    /**
     * @return array
     */
    public function toArray(): array {
        return [
            'type' => $this->getErrorType(),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->context,
        ];
    }
}
