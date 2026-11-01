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
     * Acrescenta dados ao contexto da exceção e devolve a própria
     * instância (para encadear no `throw`).
     *
     * NOTA (bug corrigido, out/2026): antes fazia `$new = clone $this`.
     * O PHP não deixa clonar Exception (o __clone é privado e final), então
     * toda chamada lançava Error "Trying to clone an uncloneable object" —
     * a exceção de domínio virava 500 genérico. O GetImageUseCase chamava
     * este método 5 vezes (formato inválido, MIME, rate limit...), e
     * qualquer um desses caminhos de erro respondia 500 em vez de 400/422.
     * Exceção não precisa ser imutável: é criada, enriquecida e lançada
     * na mesma linha, então alterar a própria instância é seguro.
     *
     * @param array $context
     *
     * @return $this
     */
    public function withContext(array $context): static {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    /**
     * @return string
     */
    abstract public function getErrorType(): string;
}
