<?php

declare(strict_types = 1);

namespace App\Application\Account\UseCase\CurrentUser;

use App\Application\Account\DTO\CurrentUser\Request\GetCurrentUserRequest;
use App\Application\Account\DTO\CurrentUser\Response\GetCurrentUserResponse;
use App\Application\Shared\RequestValidator;
use App\Domain\Exception\UserNotFoundException;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Os dados da conta de quem está logado (GET /me, exige JWT).
 *
 * Lê o usuário do banco a cada chamada, em vez de devolver o que está no
 * JWT: um email confirmado ou um papel trocado depois do login aparecem na
 * hora, sem esperar o token vencer.
 *
 * NOTA (nov/2026): criado com o GET /me.
 */
final readonly class GetCurrentUserUseCase {
    /**
     * @param \App\Domain\User\Repository\UserRepositoryInterface $userRepository
     * @param \App\Application\Shared\RequestValidator            $requestValidator
     * @param \Symfony\Contracts\Translation\TranslatorInterface  $translator
     */
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RequestValidator $requestValidator,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Account\DTO\CurrentUser\Request\GetCurrentUserRequest $request
     *
     * @return \App\Application\Account\DTO\CurrentUser\Response\GetCurrentUserResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\UserNotFoundException
     */
    public function execute(GetCurrentUserRequest $request): GetCurrentUserResponse {
        $this->requestValidator->validate(
            $request,
            'current_user_request',
            ['user_id' => $request->userId],
        );

        $user = $this->userRepository->findById($request->userId)
            ?? throw UserNotFoundException::withId($request->userId);

        return GetCurrentUserResponse::success(
            id: (int) $user->getId(),
            email: (string) $user->getEmail(),
            roles: array_values($user->getRoles()),
            verified: $user->isVerified(),
            message: $this->translator->trans('Current user retrieved successfully'),
        );
    }
}
