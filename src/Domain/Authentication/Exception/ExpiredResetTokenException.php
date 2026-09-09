<?php

declare(strict_types = 1);

namespace App\Domain\Authentication\Exception;

use DomainException;
use DateTime;
use DateTimeInterface;
use Throwable;
use RuntimeException;

/**
 *
 */
class ExpiredResetTokenException extends RuntimeException {
    public ?DateTimeInterface $expiresAt = null {
        get {
            return $this->expiresAt;
        }
    }

    /**
     * @param string                  $message
     * @param int                     $code
     * @param \Throwable|null         $previous
     * @param \DateTimeInterface|null $expiresAt
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?DateTimeInterface $expiresAt = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->expiresAt = $expiresAt;
    }

    /**
     * @param \DateTimeInterface $expiresAt
     *
     * @return self
     */
    public static function create(DateTimeInterface $expiresAt): self {
        return new self(
            message: sprintf(
                'Token expired at %s (current time: %s)',
                $expiresAt->format('Y-m-d H:i:s'),
                (new DateTime())->format('Y-m-d H:i:s')
            ),
            expiresAt: $expiresAt
        );
    }
}