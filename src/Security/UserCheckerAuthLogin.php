<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use App\Entity\User;

class UserCheckerAuthLogin implements UserCheckerInterface {

    public function checkPreAuth(UserInterface $user): void {
        if (!$user instanceof User) {
            return;
        }
        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException("Almost there! Check your email to activate your account!!");
        }
    }
    public function checkPostAuth(UserInterface $user): void {
        if (!$user instanceof User) {
            return;
        }
        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException("Check your email to activate your account!!");
        }
    }
}