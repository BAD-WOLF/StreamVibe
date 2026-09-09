<?php

declare(strict_types = 1);

namespace App\Domain\Authentication\Exception;

use InvalidArgumentException;

/**
 *
 */
final class UserValidationException extends InvalidArgumentException {
    /**
     * @param string $message
     * @param array  $errors
     */
    public function __construct(
        string $message,
        public readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    /**
     * @return array
     */
    public function getErrors(): array {
        return $this->errors;
    }
}