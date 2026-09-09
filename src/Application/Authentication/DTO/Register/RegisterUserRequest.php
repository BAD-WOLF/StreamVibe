<?php

declare(strict_types = 1);

namespace App\Application\Authentication\DTO\Register;

use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 */
final class RegisterUserRequest {
    /**
     * @param string $email
     * @param string $password
     * @param bool   $agreeTerms
     * @param bool   $resendVerification
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Email cannot be blank')]
        #[Assert\Email(message: 'Please provide a valid email address'), ]
        #[Assert\Length(max: 180, maxMessage: 'Email cannot be longer than {{ limit }} characters',)]
        public private(set) string $email {
            /**
             * @return string
             */ get => $this->email;
        },

        #[Assert\NotBlank(message: 'Password cannot be blank')]
        #[Assert\Length(min: 12, minMessage: 'Password must be at least {{ limit }} characters long',), ]
        #[Assert\Length(max: 4096, maxMessage: 'Password cannot be longer than {{ limit }} characters',), ]
        #[Assert\PasswordStrength]
        #[Assert\NotCompromisedPassword, ]
        public private(set) string $password {
            /**
             * @return string
             */ get => $this->password;
        },

        #[Assert\IsTrue(message: 'You must agree to the terms and conditions',), ]
        public private(set) bool $agreeTerms = false {
            /**
             * @return bool
             */ get => $this->agreeTerms;
        },

        public private(set) bool $resendVerification = false {
            /**
             * @return bool
             */ get => $this->resendVerification;
        },
    ) {
    }

    // Virtual computed properties using property hooks
    public bool $hasAgreedToTerms {
        /**
         * @return bool
         */
        get => $this->agreeTerms;
    }

    public bool $isResendVerification {
        /**
         * @return bool
         */
        get => $this->resendVerification;
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
    public function getPassword(): string {
        return $this->password;
    }

    /**
     * @return bool
     */
    public function hasAgreedToTerms(): bool {
        return $this->agreeTerms;
    }

    /**
     * @return bool
     */
    public function isResendVerification(): bool {
        return $this->resendVerification;
    }
}
