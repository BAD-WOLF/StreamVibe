<?php

declare(strict_types = 1);

namespace App\Domain\Authentication\Exception;

use Throwable;
use RuntimeException;

/**
 *
 */
final class EmailDeliveryException extends RuntimeException {
    /**
     * @param string          $message
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}