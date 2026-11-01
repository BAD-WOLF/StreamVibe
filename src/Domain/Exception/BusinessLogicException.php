<?php

declare(strict_types = 1);

namespace App\Domain\Exception;

use Exception;

/**
 *
 */
class BusinessLogicException extends DomainException {
    /**
     * @param string          $message
     * @param int             $code
     * @param \Exception|null $previous
     * @param array           $context
     */
    public function __construct(
        string $message,
        int $code = 422,
        ?Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    /**
     * @return string
     */
    public function getErrorType(): string {
        return 'business_logic_error';
    }

    /**
     * @param string     $resource
     * @param mixed|null $identifier
     *
     * @return self
     */
    public static function notFound(string $resource, mixed $identifier = null): self {
        $message = $identifier
            ? sprintf('%s with identifier "%s" not found', $resource, $identifier)
            : sprintf('%s not found', $resource);

        return new self($message, 404, null, [
            'resource' => $resource,
            'identifier' => $identifier,
        ]);
    }

    /**
     * @param string $operation
     * @param string $reason
     *
     * @return self
     */
    public static function invalidOperation(string $operation, string $reason = ''): self {
        $message = sprintf('Invalid operation: %s', $operation);
        if ($reason) {
            $message .= sprintf(' (%s)', $reason);
        }

        return new self($message, 422, null, [
            'operation' => $operation,
            'reason' => $reason,
        ]);
    }

    /**
     * @param string $service
     * @param string $reason
     *
     * @return self
     */
    public static function serviceUnavailable(string $service, string $reason = ''): self {
        $message = sprintf('Service "%s" is temporarily unavailable', $service);
        if ($reason) {
            $message .= sprintf(': %s', $reason);
        }

        return new self($message, 503, null, [
            'service' => $service,
            'reason' => $reason,
        ]);
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
