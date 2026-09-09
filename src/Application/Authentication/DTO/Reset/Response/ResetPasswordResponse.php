<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\Reset\Response;

use DateInterval;
use DateTimeImmutable;

/**
 *
 */
final class ResetPasswordResponse {
    /**
     * @param string                  $message
     * @param string|null             $requestId
     * @param \DateTimeImmutable|null $expiresAt
     * @param bool                    $emailSent
     * @param int|null                $userId
     */
    public function __construct(
        public private(set) string $message {
            /**
             * @return string
             */ get => $this->message;
        },
        public private(set) ?string $requestId = null {
            /**
             * @return string|null
             */ get => $this->requestId;
        },
        public private(set) ?DateTimeImmutable $expiresAt = null {
            /**
             * @return \DateTimeImmutable|null
             */ get => $this->expiresAt;
        },
        public private(set) bool $emailSent = false {
            /**
             * @return bool
             */ get => $this->emailSent;
        },
        public private(set) ?int $userId = null {
            /**
             * @return int|null
             */ get => $this->userId;
        },
    ) {
    }

    /**
     * @param string                  $message
     * @param string|null             $requestId
     * @param \DateTimeImmutable|null $expiresAt
     * @param bool                    $emailSent
     * @param int|null                $userId
     *
     * @return self
     */
    public static function created(
        string $message,
        ?string $requestId = null,
        ?DateTimeImmutable $expiresAt = null,
        bool $emailSent = false,
        ?int $userId = null,
    ): self {
        return new self(
            message: $message,
            requestId: $requestId,
            expiresAt: $expiresAt,
            emailSent: $emailSent,
            userId: $userId,
        );
    }

    /**
     * @param string $message
     *
     * @return self
     */
    public static function cancelled(string $message): self {
        return new self(message: $message);
    }

    // Computed properties
    public ?DateInterval $timeUntilExpiry {
        /**
         * @return \DateInterval|false|null
         */
        get {
            if (!$this->expiresAt) {
                return null;
            }

            $now = new DateTimeImmutable();
            if ($this->expiresAt <= $now) {
                return null;
            }

            return $now->diff($this->expiresAt);
        }
    }

    public string $expiryStatus {
        /**
         * @return string
         */
        get {
            if (!$this->expiresAt) {
                return 'unknown';
            }

            $now = new DateTimeImmutable();
            if ($this->expiresAt <= $now) {
                return 'expired';
            }

            $diff = $now->diff($this->expiresAt);
            $totalMinutes = $diff->days * 24 * 60 + $diff->h * 60 + $diff->i;

            if ($totalMinutes < 5) {
                return 'expiring_soon';
            }

            if ($totalMinutes < 60) {
                return 'expires_within_hour';
            }

            return 'active';
        }
    }

    /**
     * NOTA: adicionado para consistência com o resto da família de DTOs de
     * resposta da aplicação (SearchMoviesResponse, GetMovieDetailsResponse,
     * GetPersonDetailsResponse, GetImageResponse, RegisterUserResponse —
     * todos já tinham asArray/toArray()). Também é pré-requisito para
     * SolicitationOutput (ApiPlatform) funcionar corretamente no dia em
     * que o glob do Finder em OutputMapperDiscoveryPass for corrigido e
     * esse mapeamento passar a rodar de verdade — sem toArray() aqui,
     * GenericOutputMapper não conseguia extrair nada para o campo
     * "array $data" do Output, resultando em null onde um array é exigido.
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
                'request_id' => $this->requestId,
                'expires_at' => $this->expiresAt?->format('Y-m-d H:i:s'),
                'email_sent' => $this->emailSent,
                'user_id' => $this->userId,
                'expiry_status' => $this->expiryStatus,
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
     * @return string|null
     */
    public function getRequestId(): ?string {
        return $this->requestId;
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
    public function wasEmailSent(): bool {
        return $this->emailSent;
    }

    /**
     * @return int|null
     */
    public function getUserId(): ?int {
        return $this->userId;
    }

    /**
     * @return \DateInterval|null
     */
    public function getTimeUntilExpiry(): ?DateInterval {
        return $this->timeUntilExpiry;
    }

    /**
     * @return string
     */
    public function getExpiryStatus(): string {
        return $this->expiryStatus;
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
        return $this->emailSent;
    }
}

