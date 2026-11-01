<?php

declare(strict_types = 1);

namespace App\Application\Authentication\UseCase\ResetPassword\Confirm;

use App\Application\Authentication\DTO\ResetPassword\Confirm\Request\ConfirmPasswordResetRequest;
use App\Application\Authentication\DTO\ResetPassword\Confirm\Response\ConfirmPasswordResetResponse;
use App\Application\Authentication\Port\PasswordResetInterface;
use App\Application\Authentication\Port\RefreshTokenRevokerInterface;
use App\Application\Shared\RequestValidator;
use App\Domain\User\Repository\UserRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Define a senha nova a partir do token do link de troca de senha
 * (POST /reset-password/confirm).
 *
 * 1. valida a entrada (token e senha nova, com as regras do cadastro);
 * 2. confere o token (400 inválido, 410 vencido);
 * 3. invalida o token (um link só serve uma vez);
 * 4. grava a senha nova;
 * 5. invalida os refresh tokens do usuário — quem estava logado com a
 *    senha antiga não renova mais o JWT.
 *
 * O token é invalidado antes de gravar a senha: se a gravação falhar, o
 * usuário pede outro link, em vez de ficar com um link usado e ainda
 * válido.
 *
 * NOTA (out/2026): endpoint novo. O fluxo de troca de senha tinha pedido
 * do link, conferência e cancelamento, mas nenhum jeito de definir a
 * senha nova — o link do email levava ao front, que não tinha para onde
 * mandar a senha.
 */
final readonly class ConfirmPasswordResetUseCase {
    /**
     * @param \App\Domain\User\Repository\UserRepositoryInterface                  $userRepository
     * @param \App\Application\Authentication\Port\PasswordResetInterface          $passwordReset
     * @param \App\Application\Authentication\Port\RefreshTokenRevokerInterface    $refreshTokenRevoker
     * @param \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher
     * @param \App\Application\Shared\RequestValidator                             $requestValidator
     * @param \Psr\Log\LoggerInterface                                             $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface                   $translator
     */
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordResetInterface $passwordReset,
        private RefreshTokenRevokerInterface $refreshTokenRevoker,
        private UserPasswordHasherInterface $passwordHasher,
        private RequestValidator $requestValidator,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Authentication\DTO\ResetPassword\Confirm\Request\ConfirmPasswordResetRequest $request
     *
     * @return \App\Application\Authentication\DTO\ResetPassword\Confirm\Response\ConfirmPasswordResetResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\InvalidResetTokenException
     * @throws \App\Domain\Exception\ExpiredResetTokenException
     */
    public function execute(
        ConfirmPasswordResetRequest $request,
    ): ConfirmPasswordResetResponse {
        $this->requestValidator->validate($request, 'confirm_password_reset_request');

        $validToken = $this->passwordReset->validateToken($request->token);
        $user = $validToken->user;

        $this->passwordReset->removeToken($request->token);

        $user->setPassword($this->passwordHasher->hashPassword($user, $request->password));
        $this->userRepository->save($user, flush: true);

        $revokedTokens = $this->refreshTokenRevoker->revokeAllFor($user);

        $this->logger->info('Password reset completed', [
            'user_id' => $user->getId(),
            'revoked_refresh_tokens' => $revokedTokens,
        ]);

        return ConfirmPasswordResetResponse::success(
            message: $this->translator->trans('Your password has been reset successfully.'),
        );
    }
}
