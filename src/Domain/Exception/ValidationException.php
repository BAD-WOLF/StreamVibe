<?php

declare(strict_types = 1);

namespace App\Domain\Exception;

use Exception;

/**
 *
 */
class ValidationException extends DomainException {
    public array $errors = [] {
        get {
            return $this->errors;
        }
    }

    /**
     * @param string          $message
     * @param array           $errors
     * @param int             $code
     * @param \Exception|null $previous
     * @param array           $context
     */
    public function __construct(
        string $message = 'Validation failed',
        array $errors = [],
        int $code = 400,
        ?Exception $previous = null,
        array $context = [],
    ) {
        parent::__construct($message, $code, $previous, $context);
        $this->errors = $errors;
    }

    /**
     * @return bool
     */
    public function hasErrors(): bool {
        return !empty($this->errors);
    }

    /**
     * Getter explícito para $errors.
     *
     * Necessário porque o ExceptionListener da aplicação chama
     * $exception->getErrors() genericamente para qualquer exceção de
     * validação. A propriedade $errors já é pública (com property hook),
     * mas sem este method algumas subclasses (ex.: UserValidationException
     * em App\Domain\Exception) quebravam com
     * "Call to undefined method ...::getErrors()".
     *
     * @return array
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * @return string
     */
    public function getErrorType(): string {
        return 'validation_error';
    }

    /**
     * @param array  $errors
     * @param string $message
     *
     * @return self
     */
    public static function withErrors(
        array $errors,
        string $message = 'Validation failed',
    ): self {
        return new self($message, $errors);
    }

    /**
     * @return array
     */
    public function toArray(): array {
        return [
            'type' => $this->getErrorType(),
            'message' => $this->getMessage(),
            'errors' => $this->errors,
            'context' => $this->context,
        ];
    }
}