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
     * O serviço externo recusou a chamada por limite de requisições
     * (HTTP 429 do lado dele). Para quem chama a API é indisponibilidade
     * temporária (503), não erro do cliente.
     *
     * NOTA (out/2026): criado para o adaptador do TMDB
     * (Infrastructure\ExternalServices\TmdbApiService). Antes o
     * GetImageUseCase tentava detectar "429" no texto da mensagem e lançava
     * RateLimitExceededException::apiKeyLimitExceeded(), que fala de limite
     * da NOSSA API — mensagem errada para o cliente.
     *
     * @param string   $service
     * @param int|null $retryAfter segundos sugeridos pelo serviço (Retry-After)
     * @param array    $context
     *
     * @return self
     */
    public static function rateLimited(string $service, ?int $retryAfter = null, array $context = []): self {
        return new self(
            sprintf('Rate limit reached on %s', $service), 503, null, array_merge($context, [
            'service' => $service,
            'error_type' => 'rate_limited',
            'retry_after' => $retryAfter,
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
