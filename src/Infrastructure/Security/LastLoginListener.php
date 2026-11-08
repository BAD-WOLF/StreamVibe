<?php

declare(strict_types = 1);

namespace App\Infrastructure\Security;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Registra a data do último login (User::recordLogin()) a cada login certo
 * com email e senha (POST /api/login_check), para a administração de
 * contas.
 *
 * Só no firewall "login" (security.event_dispatcher.login): no firewall
 * "api" o mesmo evento sai a cada requisição com JWT e a cada renovação do
 * access token, que não são logins.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final readonly class LastLoginListener {
    private const string LOGIN_FIREWALL_DISPATCHER = 'security.event_dispatcher.login';

    /**
     * @param \App\Domain\User\Repository\UserRepositoryInterface $userRepository
     */
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @param \Symfony\Component\Security\Http\Event\LoginSuccessEvent $event
     *
     * @return void
     */
    #[AsEventListener(event: LoginSuccessEvent::class, dispatcher: self::LOGIN_FIREWALL_DISPATCHER)]
    public function onLoginSuccess(LoginSuccessEvent $event): void {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $user->recordLogin(new DateTimeImmutable());
        $this->userRepository->save($user);
    }
}
