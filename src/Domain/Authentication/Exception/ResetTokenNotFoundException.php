<?php

declare(strict_types = 1);

namespace App\Domain\Authentication\Exception;

use DomainException;
use Throwable;
use RuntimeException;

/**
 *
 */
class ResetTokenNotFoundException extends RuntimeException {
    public array $searchCriteria = [] {
        get {
            return $this->searchCriteria;
        }
    }

    /**
     * @param string          $message
     * @param int             $code
     * @param \Throwable|null $previous
     * @param array           $searchCriteria
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        array $searchCriteria = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->searchCriteria = $searchCriteria;
    }

    /**
     * @param string $token
     *
     * @return self
     */
    public static function byToken(string $token): self {
        return new self(
            message: 'No reset request found for provided token',
            searchCriteria: [
                'token_prefix' => substr($token, 0, 10),
                'token_length' => strlen($token),
            ]
        );
    }

    /**
     * @param int $userId
     *
     * @return self
     */
    public static function byUser(int $userId): self {
        return new self(
            message: 'No reset request found for user',
            searchCriteria: [
                'user_id' => $userId,
            ]
        );
    }
}