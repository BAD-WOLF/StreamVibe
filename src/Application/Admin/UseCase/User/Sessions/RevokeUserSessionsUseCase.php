<?php

declare(strict_types = 1);

namespace App\Application\Admin\UseCase\User\Sessions;

use App\Application\Admin\DTO\User\Sessions\Request\RevokeUserSessionsRequest;
use App\Application\Admin\DTO\User\Sessions\Response\RevokeUserSessionsResponse;
use App\Application\Admin\Port\UserSessionsInterface;
use App\Application\Admin\Shared\AccountAdministration;
use App\Application\Admin\Shared\AuditTrail;
use App\Application\Shared\RequestValidator;
use App\Domain\Audit\ValueObject\AuditAction;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Encerra as sessões de uma conta em todos os navegadores
 * (DELETE /admin/users/{userId}/sessions, ROLE_ADMIN): cada um precisa de
 * um login novo quando o access token atual vencer (até 15 minutos).
 *
 * Sem sessão nenhuma, responde 0 e registra do mesmo jeito: quem encerrou
 * por precaução fica no log.
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final readonly class RevokeUserSessionsUseCase {
    /**
     * @param \App\Application\Admin\Shared\AccountAdministration $accounts
     * @param \App\Application\Admin\Port\UserSessionsInterface   $sessions
     * @param \App\Application\Admin\Shared\AuditTrail            $auditTrail
     * @param \App\Application\Shared\RequestValidator            $requestValidator
     * @param \Symfony\Contracts\Translation\TranslatorInterface  $translator
     */
    public function __construct(
        private AccountAdministration $accounts,
        private UserSessionsInterface $sessions,
        private AuditTrail $auditTrail,
        private RequestValidator $requestValidator,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Admin\DTO\User\Sessions\Request\RevokeUserSessionsRequest $request
     *
     * @return \App\Application\Admin\DTO\User\Sessions\Response\RevokeUserSessionsResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\UserNotFoundException
     * @throws \App\Domain\Exception\PermissionDeniedException
     */
    public function execute(RevokeUserSessionsRequest $request): RevokeUserSessionsResponse {
        $this->requestValidator->validate($request, 'admin_revoke_sessions_request', ['user_id' => $request->userId]);

        $actor = $this->accounts->actor($request->actorId);
        $user = $this->accounts->managedAccount($actor, $request->userId);

        $revokedSessions = $this->sessions->revokeAllFor($user);
        $this->auditTrail->record(
            AuditAction::SessionsRevoked,
            $actor,
            $user,
            ['revoked_sessions' => $revokedSessions],
            flush: true,
        );

        return RevokeUserSessionsResponse::success(
            userId: (int) $user->getId(),
            revokedSessions: $revokedSessions,
            message: $this->translator->trans('User sessions revoked successfully'),
        );
    }
}
