<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\Reset\Response;

use DateTime;
use Symfony\Component\Serializer\Attribute\Ignore;

final class CancelResetResponse {
    public function __construct(
        public private(set) string $message {
            get => $this->message;
        },

        public private(set) bool $cancelled = true {
            get => $this->cancelled;
        },

        public private(set) ?string $token = null {
            get => $this->token;
        },

        public private(set) ?DateTime $cancelledAt = null {
            get => $this->cancelledAt;
        },
    ) {
    }

    #[Ignore]
    public array $asArray {
        get {
            return [
                'message' => $this->message,
                'cancelled' => $this->cancelled,
                'token' => $this->token,
                'cancelled_at' => $this->cancelledAt?->format('c'),
            ];
        }
    }

    #[Ignore]
    public function isSuccess(): bool {
        return $this->cancelled;
    }

    public function getMessage(): string {
        return $this->message;
    }

    public function isCancelled(): bool {
        return $this->cancelled;
    }

    public function getToken(): ?string {
        return $this->token;
    }

    public function getCancelledAt(): ?DateTime {
        return $this->cancelledAt;
    }

    public function toArray(): array {
        return $this->asArray;
    }
}
