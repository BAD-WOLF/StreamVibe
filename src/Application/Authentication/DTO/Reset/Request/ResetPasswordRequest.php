<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\Reset\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 */
final class ResetPasswordRequest {
    /**
     * @param string      $email
     * @param string      $userAgent
     * @param string      $ipAddress
     * @param string|null $locale
     * @param bool        $sendEmail
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Email cannot be blank')]
        #[
            Assert\Email(message: 'Please provide a valid email address'),
        ]
        #[
            Assert\Length(
                max: 180,
                maxMessage: 'Email cannot be longer than {{ limit }} characters',
            ),
        ]
        public private(set) string $email {
            /**
             * @return string
             */ get => $this->email;
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

        public private(set) ?string $locale = null {
            /**
             * @return string|null
             */ get => $this->locale;
        },

        public private(set) bool $sendEmail = true {
            /**
             * @return bool
             */ get => $this->sendEmail;
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
                'email' => $this->email,
                'user_agent' => $this->userAgent,
                'ip_address' => $this->ipAddress,
                'locale' => $this->locale,
                'send_email' => $this->sendEmail,
            ];
        }
    }

    // Compatibility methods - these can be removed once all calling code is updated

    /**
     * @return string
     */
    public function getEmail(): string {
        return $this->email;
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
     * @return string|null
     */
    public function getLocale(): ?string {
        return $this->locale;
    }

    /**
     * @return bool
     */
    public function shouldSendEmail(): bool {
        return $this->sendEmail;
    }

    /**
     * @return array
     */
    public function toArray(): array {
        return $this->asArray;
    }
}
