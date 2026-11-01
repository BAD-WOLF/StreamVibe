<?php

declare(strict_types = 1);

namespace App\Application\Authentication\UseCase\Register;

use App\Application\Authentication\DTO\Register\Request\RegisterUserRequest;
use App\Application\Authentication\DTO\Register\Response\RegisterUserResponse;
use App\Application\Authentication\Port\EmailVerificationInterface;
use App\Application\Shared\RequestValidator;
use App\Domain\Exception\UserAlreadyExistsException;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Cadastro de usuário: cria a conta (não verificada) e manda o email de
 * confirmação.
 *
 * - email já cadastrado e verificado: 409 (UserAlreadyExistsException);
 * - email já cadastrado e NÃO verificado: manda o email de novo e
 *   responde como cadastro (201), sem criar outra conta;
 * - email não sai: 503 (EmailDeliveryException), e a conta fica criada —
 *   o usuário pode pedir o reenvio.
 *
 * NOTA (out/2026):
 * - O reenvio por flag (handleResendVerification(), ligado por
 *   RegisterUserRequest::$resendVerification) saiu: virou o
 *   ResendVerificationEmailUseCase.
 * - O email (template, assunto, remetente, rota do link) saiu daqui para
 *   o adaptador da EmailVerificationInterface; o UseCase não depende mais
 *   do EmailVerifier concreto nem do TemplatedEmail.
 * - Validação pelo RequestValidator (400 com o campo), para o Request e
 *   para a entidade (UniqueEntity do email). Antes eram dois blocos
 *   manuais com mensagens sem o campo.
 * - Saiu o catch (Exception) em volta do save(), que trocava qualquer
 *   erro de banco por RuntimeException com a mensagem do banco (e um
 *   placeholder "{error}" que nunca era substituído). Email repetido por
 *   corrida entre dois cadastros agora vira 409 no próprio repositório.
 */
final readonly class RegisterUserUseCase {
    /**
     * @param \App\Domain\User\Repository\UserRepositoryInterface                  $userRepository
     * @param \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher
     * @param \App\Application\Authentication\Port\EmailVerificationInterface      $emailVerification
     * @param \App\Application\Shared\RequestValidator                             $requestValidator
     * @param \Psr\Log\LoggerInterface                                             $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface                   $translator
     */
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EmailVerificationInterface $emailVerification,
        private RequestValidator $requestValidator,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param \App\Application\Authentication\DTO\Register\Request\RegisterUserRequest $request
     *
     * @return \App\Application\Authentication\DTO\Register\Response\RegisterUserResponse
     * @throws \App\Domain\Exception\UserValidationException
     * @throws \App\Domain\Exception\UserAlreadyExistsException
     * @throws \App\Domain\Exception\EmailDeliveryException
     */
    public function execute(RegisterUserRequest $request): RegisterUserResponse {
        $this->requestValidator->validate($request, 'register_user_request');

        $existingUser = $this->userRepository->findByEmail($request->email);

        if ($existingUser !== null) {
            if ($existingUser->isVerified()) {
                throw new UserAlreadyExistsException(
                    $this->translator->trans('A user with this email already exists'),
                );
            }

            // Já cadastrado e não verificado: reenvia a confirmação.
            $this->emailVerification->sendConfirmationEmail($existingUser, isResend: true);

            $this->logger->info('Registration repeated for an unverified user; confirmation resent', [
                'user_id' => $existingUser->getId(),
            ]);

            return RegisterUserResponse::success(
                userId: (int) $existingUser->getId(),
                message: $this->translator->trans(
                    'Verification email has been sent. Please check your inbox.',
                ),
            );
        }

        $user = new User();
        $user->setEmail($request->email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $request->password));
        $user->setIsVerified(false);

        $this->requestValidator->validate($user, 'user_entity');

        $this->userRepository->save($user, flush: true);

        $this->emailVerification->sendConfirmationEmail($user);

        $this->logger->info('User registered', ['user_id' => $user->getId()]);

        return RegisterUserResponse::success(
            userId: (int) $user->getId(),
            message: $this->translator->trans(
                'User registered successfully. Please check your email to verify your account.',
            ),
        );
    }
}
