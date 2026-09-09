<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\Reset\Response;

/**
 *
 */
final class GetUserResetRequestsResponse {
    /**
     * @param int    $userId
     * @param int    $activeRequestsCount
     * @param array  $requests
     * @param string $message
     */
    public function __construct(
        public private(set) int $userId {
            /**
             * @return int
             */ get => $this->userId;
        },

        public private(set) int $activeRequestsCount {
            /**
             * @return int
             */ get => $this->activeRequestsCount;
        },

        /** @var array<array{id: int, created_at: \DateTime, expires_at: \DateTime, is_expired: bool}> */
        public private(set) array $requests = [] {
            /**
             * @return array[]
             */ get => $this->requests;
        },

        public private(set) string $message = 'Reset requests retrieved successfully' {
            /**
             * @return string
             */ get => $this->message;
        },
    ) {
    }

    // Virtual computed property using property hooks
    public array $asArray {
        /**
         * @return array
         */
        get {
            return [
                'user_id' => $this->userId,
                'active_requests' => $this->activeRequestsCount,
                'message' => $this->message,
                'requests' => array_map(callback: function ($request) {
                    return [
                        'id' => $request['id'],
                        'created_at' => $request['created_at']->format('c'),
                        'expires_at' => $request['expires_at']->format('c'),
                        'is_expired' => $request['is_expired'],
                    ];
                }, array: $this->requests),
            ];
        }
    }

    // Compatibility methods - these can be removed once all calling code is updated

    /**
     * @return int
     */
    public function getUserId(): int {
        return $this->userId;
    }

    /**
     * @return int
     */
    public function getActiveRequestsCount(): int {
        return $this->activeRequestsCount;
    }

    /**
     * @return array[]
     */
    public function getRequests(): array {
        return $this->requests;
    }

    /**
     * @return string
     */
    public function getMessage(): string {
        return $this->message;
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
        return true;
    }
}
