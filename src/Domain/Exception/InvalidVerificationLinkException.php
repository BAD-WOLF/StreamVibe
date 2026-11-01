<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use Exception;

/**
 * Link de verificação de email inválido, adulterado ou vencido.
 *
 * NOTA (out/2026): antes o EmailVerificationController capturava a
 * VerifyEmailExceptionInterface do bundle e montava o JSON de erro na mão
 * (formato diferente do resto da API). Agora o adaptador
 * (Infrastructure\Security\EmailVerifier) traduz a exceção do bundle para
 * esta, e o ExceptionListener responde 400 no formato padrão
 * (success/error{type,message}).
 */
class InvalidVerificationLinkException extends DomainException
{
    /**
     * @param string          $message
     * @param int             $code
     * @param \Exception|null $previous
     * @param array           $context
     */
    public function __construct(
        string $message,
        int $code = 400,
        ?Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    /**
     * @return string
     */
    public function getErrorType(): string
    {
        return 'invalid_verification_link_error';
    }

    /**
     * @param string          $reason   motivo devolvido pelo VerifyEmailBundle
     * @param int             $userId
     * @param \Exception|null $previous
     *
     * @return self
     */
    public static function fromReason(
        string $reason,
        int $userId,
        ?Exception $previous = null,
    ): self {
        return new self(
            $reason,
            400,
            $previous,
            ['user_id' => $userId],
        );
    }
}
