<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use App\Entity\User;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserCheckerAuthLogin implements UserCheckerInterface {

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator) {
        $this->translator = $translator;
    }

    public function checkPreAuth(UserInterface $user): void {
        if (!$user instanceof User) {
            return;
        }
        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException($this->translator->trans("Almost there! Check your email to activate your account!!"));
        }
    }
    public function checkPostAuth(UserInterface $user): void {
        if (!$user instanceof User) {
            return;
        }
        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException($this->translator->trans("Check your email to activate your account!!"));
        }
    }
}