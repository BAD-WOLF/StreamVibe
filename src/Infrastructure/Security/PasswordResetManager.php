<?php
declare(strict_types = 1);

namespace App\Infrastructure\Security;

use App\Application\Authentication\Port\PasswordResetInterface;
use App\Application\Authentication\Port\PasswordResetToken;
use App\Application\Authentication\Port\ValidatedPasswordResetToken;
use App\Domain\Authentication\Repository\ResetPasswordSolicitationRepositoryInterface;
use App\Domain\Exception\EmailDeliveryException;
use App\Domain\Exception\ExpiredResetTokenException;
use App\Domain\Exception\InvalidResetTokenException;
use App\Domain\Exception\RateLimitExceededException;
use App\Domain\Exception\ResetTokenNotFoundException;
use App\Domain\User\Entity\User;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ExpiredResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\TooManyPasswordRequestsException;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * Implementação da PasswordResetInterface com o ResetPasswordBundle e o
 * Mailer. Traduz as exceções do bundle para as do Domain:
 *
 * | bundle                              | Domain                          | HTTP |
 * |-------------------------------------|---------------------------------|------|
 * | TooManyPasswordRequestsException    | RateLimitExceededException      | 429  |
 * | ExpiredResetPasswordTokenException  | ExpiredResetTokenException      | 410  |
 * | outras (token inválido/inexistente) | InvalidResetTokenException      | 400  |
 * | removeResetRequest() sem pedido     | ResetTokenNotFoundException     | 404  |
 *
 * NOTA (out/2026): criado na divisão do antigo ResetUserPasswordUseCase.
 * O que era repetido lá em cada método (chamar o bundle, converter
 * DateTime em DateTimeImmutable, mapear as exceções, montar o
 * TemplatedEmail) ficou aqui uma vez. O email usa o mesmo template
 * (reset_password/email.html.twig) com as mesmas variáveis; o
 * "request_info" (IP e user agent) que ia no contexto saiu — o template
 * nunca o usou.
 */
final readonly class PasswordResetManager implements PasswordResetInterface {
    /**
     * Tamanho do "selector" no começo do token (ResetPasswordHelper::SELECTOR_LENGTH,
     * que é privado no bundle).
     */
    private const int SELECTOR_LENGTH = 20;

    private const string EMAIL_TEMPLATE = 'reset_password/email.html.twig';

    /**
     * @param \SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface                    $resetPasswordHelper
     * @param \App\Domain\Authentication\Repository\ResetPasswordSolicitationRepositoryInterface $resetRequestRepository
     * @param \Symfony\Component\Mailer\MailerInterface                                          $mailer
     * @param \Symfony\Contracts\Translation\TranslatorInterface                                 $translator
     * @param \Psr\Log\LoggerInterface                                                           $logger
     * @param string                                                                             $senderAddress
     * @param string                                                                             $senderName
     */
    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private ResetPasswordSolicitationRepositoryInterface $resetRequestRepository,
        private MailerInterface $mailer,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
        private string $senderAddress,
        private string $senderName,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function generateToken(User $user): PasswordResetToken {
        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (TooManyPasswordRequestsException $exception) {
            throw new RateLimitExceededException(
                message: $this->translator->trans(
                    'Too many password reset requests. Please wait before trying again.',
                ),
                previous: $exception,
                context: ['retry_after' => max(0, $exception->getRetryAfter())],
            );
        }

        return new PasswordResetToken(
            token: $resetToken->getToken(),
            expiresAt: DateTimeImmutable::createFromInterface($resetToken->getExpiresAt()),
            expirationMessageKey: $resetToken->getExpirationMessageKey(),
            expirationMessageData: $resetToken->getExpirationMessageData(),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function sendResetEmail(User $user, PasswordResetToken $token): void {
        $email = new TemplatedEmail()
            ->from(new Address($this->senderAddress, $this->senderName))
            ->to((string) $user->getEmail())
            ->subject($this->translator->trans('Password Reset Request - StreamVibe'))
            ->htmlTemplate(self::EMAIL_TEMPLATE)
            ->context([
                'user' => $user,
                'resetToken' => $token,
                'app_name' => $this->senderName,
            ]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('Failed to send the password reset email', [
                'user_id' => $user->getId(),
                'error' => $exception->getMessage(),
            ]);

            throw new EmailDeliveryException(
                message: 'Failed to send the password reset email',
                previous: $exception,
                context: ['user_id' => $user->getId()],
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateToken(string $token): ValidatedPasswordResetToken {
        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ExpiredResetPasswordTokenException $exception) {
            throw ExpiredResetTokenException::tokenExpired($token);
        } catch (ResetPasswordExceptionInterface $exception) {
            throw InvalidResetTokenException::fromReason(
                $exception->getReason(),
                $exception instanceof \Exception ? $exception : null,
            );
        }

        if (!$user instanceof User) {
            throw InvalidResetTokenException::fromReason('The password reset token does not belong to a user');
        }

        // O bundle já conferiu o token; a solicitação dele dá a validade.
        $resetRequest = $this->resetRequestRepository->findBySelector(
            substr($token, 0, self::SELECTOR_LENGTH),
        );

        if ($resetRequest === null) {
            throw ResetTokenNotFoundException::byToken($token);
        }

        return new ValidatedPasswordResetToken(
            user: $user,
            expiresAt: DateTimeImmutable::createFromInterface($resetRequest->getExpiresAt()),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function removeToken(string $token): void {
        try {
            $this->resetPasswordHelper->removeResetRequest($token);
        } catch (ResetPasswordExceptionInterface $exception) {
            throw ResetTokenNotFoundException::byToken($token);
        }
    }
}
