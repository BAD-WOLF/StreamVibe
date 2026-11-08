<?php

declare(strict_types = 1);

namespace App\Application\Admin\UseCase\AuditLog\List;

use App\Application\Admin\DTO\AuditLog\List\Request\ListAuditLogRequest;
use App\Application\Admin\DTO\AuditLog\List\Response\ListAuditLogResponse;
use App\Application\Admin\DTO\Shared\Response\Model\AuditLogAccount;
use App\Application\Admin\DTO\Shared\Response\Model\AuditLogEntrySummary;
use App\Application\Shared\RequestValidator;
use App\Domain\Audit\Entity\AuditLogEntry;
use App\Domain\Audit\Repository\AuditLogRepositoryInterface;
use App\Domain\Audit\ValueObject\AuditAction;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Lista o log de auditoria, 20 entradas por página, da mais nova para a
 * mais antiga (GET /admin/audit-log, ROLE_ADMIN), com os filtros de conta
 * (?user_id=) e de ação (?action=).
 *
 * NOTA (nov/2026): criado com a administração de contas.
 */
final readonly class ListAuditLogUseCase {
    private const int PER_PAGE = 20;

    /**
     * @param \App\Domain\Audit\Repository\AuditLogRepositoryInterface $auditLogRepository
     * @param \App\Application\Shared\RequestValidator                 $requestValidator
     * @param \Symfony\Contracts\Translation\TranslatorInterface       $translator
     */
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
        private RequestValidator $requestValidator,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Admin\DTO\AuditLog\List\Request\ListAuditLogRequest $request
     *
     * @return \App\Application\Admin\DTO\AuditLog\List\Response\ListAuditLogResponse
     * @throws \App\Domain\Exception\UserValidationException
     */
    public function execute(ListAuditLogRequest $request): ListAuditLogResponse {
        $this->requestValidator->validate($request, 'admin_audit_log_request', ['page' => $request->page]);

        $found = $this->auditLogRepository->findPage(
            $request->page,
            self::PER_PAGE,
            $request->userId,
            $request->action === null ? null : AuditAction::from($request->action),
        );

        return ListAuditLogResponse::success(
            entries: array_map(
                static fn(AuditLogEntry $entry): AuditLogEntrySummary => new AuditLogEntrySummary(
                    id: (int) $entry->id,
                    action: $entry->action->value,
                    occurredAt: $entry->occurredAt,
                    actor: self::account($entry->actorId, $entry->actorEmail),
                    target: self::account($entry->targetUserId, $entry->targetEmail),
                    details: $entry->details,
                ),
                $found['entries'],
            ),
            page: $request->page,
            perPage: self::PER_PAGE,
            totalResults: $found['total'],
            message: $this->translator->trans('Audit log retrieved successfully'),
        );
    }

    /**
     * @param int|null    $id
     * @param string|null $email
     *
     * @return \App\Application\Admin\DTO\Shared\Response\Model\AuditLogAccount|null
     */
    private static function account(?int $id, ?string $email): ?AuditLogAccount {
        return $id === null || $email === null ? null : new AuditLogAccount(id: $id, email: $email);
    }
}
