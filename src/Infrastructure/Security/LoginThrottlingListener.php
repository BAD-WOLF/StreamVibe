<?php

declare(strict_types = 1);

namespace App\Infrastructure\Security;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Limita as tentativas de login (POST /api/login_check) com o
 * LoginAttemptLimiter:
 * - antes de conferir a senha, recusa a tentativa de quem já passou do
 *   limite (sem consultar o banco nem calcular o hash da senha);
 * - conta cada login que falhou e zera a contagem da conta no login certo;
 * - a recusa responde 429 com Retry-After (segundos), no mesmo formato
 *   dos outros erros do login ({"code": 429, "message": "..."}).
 *
 * Só no firewall "login": os listeners ficam no event dispatcher dele
 * (security.event_dispatcher.login). No firewall "api" o autenticador do
 * JWT também dispara esses eventos, a cada requisição.
 *
 * O IP é o Request::getClientIp(): atrás de um proxy, ele só é o do
 * cliente com o proxy configurado como confiável (trusted_proxies).
 *
 * NOTA (nov/2026): criado na revisão de segurança. Antes não havia limite
 * nenhum na aplicação: dava para testar senhas sem parar — só o nginx de
 * produção limitava, por IP, a 1 requisição por segundo.
 */
final readonly class LoginThrottlingListener {
    private const string LOGIN_FIREWALL_DISPATCHER = 'security.event_dispatcher.login';

    /**
     * @param \App\Infrastructure\Security\LoginAttemptLimiter          $limiter
     * @param \Symfony\Component\HttpFoundation\RequestStack            $requestStack
     * @param \Symfony\Contracts\Translation\TranslatorInterface        $translator
     */
    public function __construct(
        private LoginAttemptLimiter $limiter,
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * Prioridade 2080, a mesma do login_throttling do Symfony: antes da
     * checagem da conta e da senha.
     *
     * @param \Symfony\Component\Security\Http\Event\CheckPassportEvent $event
     *
     * @return void
     * @throws \Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException
     */
    #[AsEventListener(event: CheckPassportEvent::class, priority: 2080, dispatcher: self::LOGIN_FIREWALL_DISPATCHER)]
    public function onCheckPassport(CheckPassportEvent $event): void {
        $request = $this->requestStack->getMainRequest();

        if ($request === null) {
            return;
        }

        $secondsUntilAllowed = $this->limiter->secondsUntilAllowed(
            $this->clientIp($request),
            $this->emailOf($event->getPassport()),
        );

        if ($secondsUntilAllowed > 0) {
            throw new TooManyLoginAttemptsAuthenticationException((int) ceil($secondsUntilAllowed / 60));
        }
    }

    /**
     * @param \Symfony\Component\Security\Http\Event\LoginFailureEvent $event
     *
     * @return void
     */
    #[AsEventListener(event: LoginFailureEvent::class, dispatcher: self::LOGIN_FIREWALL_DISPATCHER)]
    public function onLoginFailure(LoginFailureEvent $event): void {
        $ip = $this->clientIp($event->getRequest());
        $email = $this->emailOf($event->getPassport());
        $exception = $event->getException();

        if ($exception instanceof TooManyLoginAttemptsAuthenticationException) {
            $event->setResponse($this->tooManyAttemptsResponse(
                $exception,
                $this->limiter->secondsUntilAllowed($ip, $email),
            ));

            return;
        }

        $this->limiter->recordFailure($ip, $email);
    }

    /**
     * @param \Symfony\Component\Security\Http\Event\LoginSuccessEvent $event
     *
     * @return void
     */
    #[AsEventListener(event: LoginSuccessEvent::class, dispatcher: self::LOGIN_FIREWALL_DISPATCHER)]
    public function onLoginSuccess(LoginSuccessEvent $event): void {
        $email = $this->emailOf($event->getPassport());

        if ($email !== null) {
            $this->limiter->recordSuccess($this->clientIp($event->getRequest()), $email);
        }
    }

    /**
     * O usuário que a tentativa informou (o email do corpo do login), sem
     * consultar o banco.
     *
     * @param \Symfony\Component\Security\Http\Authenticator\Passport\Passport|null $passport
     *
     * @return string|null
     */
    private function emailOf(?Passport $passport): ?string {
        if ($passport === null || !$passport->hasBadge(UserBadge::class)) {
            return null;
        }

        $badge = $passport->getBadge(UserBadge::class);

        return $badge instanceof UserBadge ? $badge->getUserIdentifier() : null;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return string
     */
    private function clientIp(Request $request): string {
        return $request->getClientIp() ?? '';
    }

    /**
     * @param \Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException $exception
     * @param int                                                                                 $retryAfter segundos
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    private function tooManyAttemptsResponse(
        TooManyLoginAttemptsAuthenticationException $exception,
        int $retryAfter,
    ): JsonResponse {
        return new JsonResponse(
            [
                'code' => Response::HTTP_TOO_MANY_REQUESTS,
                'message' => $this->translator->trans(
                    $exception->getMessageKey(),
                    $exception->getMessageData(),
                    'security',
                ),
            ],
            Response::HTTP_TOO_MANY_REQUESTS,
            ['Retry-After' => (string) max(1, $retryAfter)],
        );
    }
}
