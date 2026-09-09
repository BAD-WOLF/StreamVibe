<?php

declare(strict_types = 1);

namespace App\Domain\Authentication\Exception;

use InvalidArgumentException;

/**
 *
 */
final class ResetPasswordValidationException extends InvalidArgumentException {
    /**
     * Construct the exception.
     *
     * @param string $message [optional] The Exception message to throw.
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