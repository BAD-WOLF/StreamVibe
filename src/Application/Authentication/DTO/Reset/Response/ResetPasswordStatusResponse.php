<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\Reset\Response;

use DateTimeImmutable;

/**
 *
 */
final class ResetPasswordStatusResponse {
    /**
     * @param string                  $message
     * @param bool                    $valid
     * @param bool                    $expired
     * @param bool                    $used
     * @param \DateTimeImmutable|null $expiresAt
     */
    public function __construct(
        public private(set) string $message {
            /**
             * @return string
             */ get => $this->message;
        },
        public private(set) bool $valid = false {
            /**
             * @return bool
             */ get => $this->valid;
        },
        public private(set) bool $expired = false {
            /**
             * @return bool
             */ get => $this->expired;
        },
        public private(set) bool $used = false {
            /**
             * @return bool
             */ get => $this->used;
        },
        public private(set) ?DateTimeImmutable $expiresAt = null {
            /**
             * @return \DateTimeImmutable|null
             */ get => $this->expiresAt;
        },
    ) {
    }

    /**
     * @param string                  $message
     * @param bool                    $expired
     * @param \DateTimeImmutable|null $expiresAt
     *
     * @return self
     */
    public static function valid(
        string $message,
        bool $expired = false,
        ?DateTimeImmutable $expiresAt = null,
    ): self {
        return new self(
            message: $message,
            valid: true,
            expired: $expired,
            used: false,
            expiresAt: $expiresAt,
        );
    }

    // Computed properties
    public bool $isTokenActive {
        /**
         * @return bool
         */
        get => $this->valid && !$this->expired && !$this->used;
    }

    /**
     * NOTA: adicionado pelo mesmo motivo do ResetPasswordResponse::$asArray
     * — consistência com a família de DTOs e pré-requisito para
     * CheckResetStatusOutput (ApiPlatform) funcionar corretamente quando
     * o glob do Finder em OutputMapperDiscoveryPass for corrigido.
     *
     * @return array
     */
    public array $asArray {
        /**
         * @return array
         */
        get {
            return [
                'message' => $this->message,
                'valid' => $this->valid,
                'expired' => $this->expired,
                'used' => $this->used,
                'expires_at' => $this->expiresAt?->format('Y-m-d H:i:s'),
                'is_token_active' => $this->isTokenActive,
            ];
        }
    }

    // Compatibility methods

    /**
     * @return string
     */
    public function getMessage(): string {
        return $this->message;
    }

    /**
     * @return bool
     */
    public function isValid(): bool {
        return $this->valid;
    }

    /**
     * @return bool
     */
    public function isExpired(): bool {
        return $this->expired;
    }

    /**
     * @return bool
     */
    public function isUsed(): bool {
        return $this->used;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getExpiresAt(): ?DateTimeImmutable {
        return $this->expiresAt;
    }

    /**
     * @return bool
     */
    public function isTokenActive(): bool {
        return $this->isTokenActive;
    }

    /**
     * @return array
     */
    public function toArray(): array {
        return $this->asArray;
    }

    /**
     * Check if the response represents a successful operation
     *
     * @return bool
     */
    public function isSuccess(): bool {
        return $this->valid;
    }
}
