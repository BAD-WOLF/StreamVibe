<?php

declare(strict_types = 1);

namespace App\Domain\Exception;

use Exception;

/**
 *
 */
abstract class DomainException extends Exception {
    public array $context = [] {
        get {
            return $this->context;
        }
    }

    /**
     * @param string          $message
     * @param int             $code
     * @param \Exception|null $previous
     * @param array           $context
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Getter explícito para $context.
     *
     * Necessário porque o ExceptionListener chama $exception->getContext()
     * genericamente em praticamente todos os handlers. A propriedade
     * $context já é pública (com property hook), mas sem este método
     * qualquer subclasse quebra com "Call to undefined method getContext()".
     *
     * @return array
     */
    public function getContext(): array {
        return $this->context;
    }

    /**
     * @param array $context
     *
     * @return $this
     */
    public function withContext(array $context): static {
        $new = clone $this;
        $new->context = array_merge($this->context, $context);

        return $new;
    }

    /**
     * @return string
     */
    abstract public function getErrorType(): string;
}