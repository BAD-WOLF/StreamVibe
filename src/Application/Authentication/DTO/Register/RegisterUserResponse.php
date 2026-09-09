<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\Register;

/**
 *
 */
final class RegisterUserResponse {
    /**
     * @param string      $message
     * @param int         $userId
     * @param bool        $emailSent
     * @param string|null $verificationUrl
     */
    public function __construct(
        public private(set) string $message {
            /**
             * @return string
             */ get => $this->message;
        },
        public private(set) int $userId {
            /**
             * @return int
             */ get => $this->userId;
        },
        public private(set) bool $emailSent = true {
            /**
             * @return bool
             */ get => $this->emailSent;
        },
        public private(set) ?string $verificationUrl = null {
            /**
             * @return string|null
             */ get => $this->verificationUrl;
        },
    ) {
    }

    /**
     * @param string      $message
     * @param int         $userId
     * @param bool        $emailSent
     * @param string|null $verificationUrl
     *
     * @return self
     */
    public static function success(
        string $message,
        int $userId,
        bool $emailSent = true,
        ?string $verificationUrl = null,
    ): self {
        return new self(
            message: $message,
            userId: $userId,
            emailSent: $emailSent,
            verificationUrl: $verificationUrl,
        );
    }

    /**
     * @param string      $message
     * @param int         $userId
     * @param string|null $verificationUrl
     *
     * @return self
     */
    public static function emailResent(
        string $message,
        int $userId,
        ?string $verificationUrl = null,
    ): self {
        return new self(
            message: $message,
            userId: $userId,
            emailSent: true,
            verificationUrl: $verificationUrl,
        );
    }

    // Computed properties using virtual property hooks
    public bool $wasEmailSent {
        /**
         * @return bool
         */
        get => $this->emailSent;
    }

    // Compatibility methods

    /**
     * @return string
     */
    public function getMessage(): string {
        return $this->message;
    }

    /**
     * @return int
     */
    public function getUserId(): int {
        return $this->userId;
    }

    /**
     * @return bool
     */
    public function wasEmailSent(): bool {
        return $this->emailSent;
    }

    /**
     * @return string|null
     */
    public function getVerificationUrl(): ?string {
        return $this->verificationUrl;
    }

    /**
     * Sempre true: RegisterUserUseCase::execute() só retorna este DTO nos
     * caminhos de sucesso (usuário criado, ou email de verificação
     * reenviado). Toda falha (validação, usuário já existe, usuário não
     * encontrado, erro no envio de email) lança exceção antes de chegar a
     * um retorno — não existe estado interno de "falha" a representar
     * aqui, diferente de ResetPasswordResponse/ResetPasswordStatusResponse,
     * que podem retornar normalmente mesmo em cenários parciais (ex.:
     * emailSent: false).
     *
     * @return bool
     */
    public function isSuccess(): bool {
        return true;
    }
}

