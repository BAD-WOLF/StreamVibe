<?php
declare(strict_types = 1);

namespace App\Infrastructure\Security;

use App\Domain\User\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *
 */
class UserCheckerAuthLogin implements UserCheckerInterface {
    /**
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator
     */
    public function __construct(private TranslatorInterface $translator) {
    }

    /**
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     *
     * @return void
     */
    public function checkPreAuth(UserInterface $user): void {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException(
                $this->translator->trans(
                    'Almost there! Check your email to activate your account!!',
                ),
            );
        }
    }

    /**
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     *
     * @return void
     */
    public function checkPostAuth(UserInterface $user): void {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException(
                $this->translator->trans(
                    'Check your email to activate your account!!',
                ),
            );
        }
    }
}
