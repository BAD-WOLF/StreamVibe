<?php

declare(strict_types = 1);

namespace App\Application\Authentication\UseCase\ResetPassword\UserRequests;

use App\Application\Authentication\DTO\ResetPassword\UserRequests\Request\GetUserPasswordResetRequestsRequest;
use App\Application\Authentication\DTO\ResetPassword\UserRequests\Response\GetUserPasswordResetRequestsResponse;
use App\Application\Authentication\DTO\ResetPassword\UserRequests\Response\Model\PasswordResetRequestSummary;
use App\Application\Shared\RequestValidator;
use App\Domain\Authentication\Entity\ResetPasswordSolicitation;
use App\Domain\Authentication\Repository\ResetPasswordSolicitationRepositoryInterface;
use App\Domain\Exception\PermissionDeniedException;
use App\Domain\Exception\UserNotFoundException;
use App\Domain\User\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Lista as solicitações de troca de senha ainda válidas de um usuário
 * (GET /reset-password/requests/{userId}, exige JWT).
 *
 * Regra de acesso: só o próprio usuário ou quem tem ROLE_ADMIN. Outro
 * usuário logado recebe 403 (PermissionDeniedException).
 *
 * Ordem: valida a entrada (400), confere a permissão (403) e só então
 * consulta o banco — quem não tem permissão não descobre se o usuário
 * existe.
 *
 * NOTA (out/2026): era ResetUserPasswordUseCase::getUserActiveRequests().
 * - Corrige o IDOR: a rota devolvia as solicitações de qualquer usuário
 *   para qualquer pessoa logada.
 * - Usuário inexistente agora é 404 (antes, lista vazia).
 * - Saiu o catch (Exception) que transformava qualquer erro de banco em
 *   lista vazia (200).
 */
final readonly class GetUserPasswordResetRequestsUseCase {
    /**
     * @param \App\Domain\User\Repository\UserRepositoryInterface                                $userRepository
     * @param \App\Domain\Authentication\Repository\ResetPasswordSolicitationRepositoryInterface $resetRequestRepository
     * @param \App\Application\Shared\RequestValidator                                           $requestValidator
     * @param \Symfony\Contracts\Translation\TranslatorInterface                                 $translator
     */
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private ResetPasswordSolicitationRepositoryInterface $resetRequestRepository,
        private RequestValidator $requestValidator,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Authentication\DTO\ResetPassword\UserRequests\Request\GetUserPasswordResetRequestsRequest $request
     *
     * @return \App\Application\Authentication\DTO\ResetPassword\UserRequests\Response\GetUserPasswordResetRequestsResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\PermissionDeniedException
     * @throws \App\Domain\Exception\UserNotFoundException
     */
    public function execute(
        GetUserPasswordResetRequestsRequest $request,
    ): GetUserPasswordResetRequestsResponse {
        $this->requestValidator->validate(
            $request,
            'user_password_reset_requests_request',
            ['user_id' => $request->userId],
        );

        if (!$request->requesterIsAdmin && $request->requesterId !== $request->userId) {
            throw PermissionDeniedException::notOwner(
                'password reset requests',
                $request->userId,
                $request->requesterId,
            );
        }

        $user = $this->userRepository->findById($request->userId)
            ?? throw UserNotFoundException::withId($request->userId);

        $activeRequests = array_map(
            static fn(ResetPasswordSolicitation $resetRequest): PasswordResetRequestSummary => new PasswordResetRequestSummary(
                id: (int) $resetRequest->getId(),
                createdAt: DateTimeImmutable::createFromInterface($resetRequest->getRequestedAt()),
                expiresAt: DateTimeImmutable::createFromInterface($resetRequest->getExpiresAt()),
            ),
            $this->resetRequestRepository->findActiveRequestsForUser($user),
        );

        return GetUserPasswordResetRequestsResponse::success(
            userId: $request->userId,
            activeRequests: array_values($activeRequests),
            message: $this->translator->trans('User reset requests retrieved successfully'),
        );
    }
}
