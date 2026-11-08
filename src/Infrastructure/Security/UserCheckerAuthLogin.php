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
 * Regras da conta no login (firewall "login", POST /api/login_check):
 * quem ainda não confirmou o email não entra.
 *
 * NOTA (nov/2026): a conferência saiu do checkPreAuth(), que roda ANTES da
 * senha. Ali, qualquer pessoa descobria que um email tinha conta ainda não
 * confirmada mandando uma senha qualquer: a resposta era "confirme seu
 * email" em vez de "credenciais inválidas". No checkPostAuth(), que roda
 * depois da senha certa, a mensagem só aparece para o dono da conta. A
 * mensagem é a que o checkPostAuth() já usava.
 */
class UserCheckerAuthLogin implements UserCheckerInterface {
    /**
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator
     */
    public function __construct(private TranslatorInterface $translator) {
    }

    /**
     * Antes da senha: nada a conferir (ver a NOTA da classe).
     *
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     *
     * @return void
     */
    public function checkPreAuth(UserInterface $user): void {
    }

    /**
     * Depois da senha certa: a conta precisa ter o email confirmado.
     *
     * NOTA (nov/2026): $token entrou na assinatura. Na 7.4 do Symfony ele é
     * um parâmetro "virtual" da UserCheckerInterface (comentado); na 8.0
     * passa a fazer parte dela, e uma implementação sem ele deixaria de
     * compilar na atualização.
     *
     * @param \Symfony\Component\Security\Core\User\UserInterface                       $user
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface|null $token
     *
     * @return void
     */
    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void {
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
