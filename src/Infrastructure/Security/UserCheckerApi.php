<?php

declare(strict_types = 1);

namespace App\Infrastructure\Security;

use App\Domain\User\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Regras da conta no firewall "api" (cada requisição com JWT e cada
 * renovação do access token): uma conta bloqueada é recusada (401), mesmo
 * com um access token emitido antes do bloqueio.
 *
 * O autenticador do JWT carrega a conta do banco a cada requisição
 * (provider app_user_provider), então o bloqueio vale na hora — sem
 * esperar o token vencer.
 *
 * NOTA (nov/2026): criado com a administração de contas. O firewall
 * "login" tem o UserCheckerAuthLogin, que também recusa a conta
 * bloqueada, mas só depois da senha certa.
 */
final readonly class UserCheckerApi implements UserCheckerInterface {
    /**
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator
     */
    public function __construct(private TranslatorInterface $translator) {
    }

    /**
     * Antes de aceitar o token: a conta não pode estar bloqueada. Aqui não
     * há senha a proteger (o token já prova quem é).
     *
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     *
     * @return void
     */
    public function checkPreAuth(UserInterface $user): void {
        if ($user instanceof User && $user->isBlocked()) {
            throw new CustomUserMessageAccountStatusException(
                $this->translator->trans('This account has been blocked.'),
            );
        }
    }

    /**
     * Nada a conferir depois.
     *
     * @param \Symfony\Component\Security\Core\User\UserInterface                       $user
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface|null $token
     *
     * @return void
     */
    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void {
    }
}
