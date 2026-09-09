<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\Reset\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 */
final class CancelResetRequest {
    /**
     * @param string $token
     * @param string $userAgent
     * @param string $ipAddress
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Token cannot be blank')]
        #[Assert\Length(
            min: 1,
            minMessage: 'Token cannot be empty'
        )]
        public private(set) string $token {
            /**
             * @return string
             */ get => $this->token;
        },

        public private(set) string $userAgent = '' {
            /**
             * @return string
             */ get => $this->userAgent;
        },

        public private(set) string $ipAddress = '' {
            /**
             * @return string
             */ get => $this->ipAddress;
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
                'token' => $this->token,
                'user_agent' => $this->userAgent,
                'ip_address' => $this->ipAddress,
            ];
        }
    }

    // Compatibility methods - these can be removed once all calling code is updated

    /**
     * @return string
     */
    public function getToken(): string {
        return $this->token;
    }

    /**
     * @return string
     */
    public function getUserAgent(): string {
        return $this->userAgent;
    }

    /**
     * @return string
     */
    public function getIpAddress(): string {
        return $this->ipAddress;
    }

    /**
     * @return array
     */
    public function toArray(): array {
        return $this->asArray;
    }
}
