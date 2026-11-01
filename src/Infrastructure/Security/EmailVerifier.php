<?php
declare(strict_types = 1);

namespace App\Infrastructure\Security;

use App\Application\Authentication\Port\EmailVerificationInterface;
use App\Domain\Exception\EmailDeliveryException;
use App\Domain\Exception\InvalidVerificationLinkException;
use App\Domain\User\Entity\User;
use App\Infrastructure\ApiPlatform\Authentication\EmailVerification\Verify\VerifyEmailEntryPoint;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * Implementação da EmailVerificationInterface com o VerifyEmailBundle e o
 * Mailer: monta e manda o email de confirmação com o link assinado, e
 * confere o link quando o usuário o abre.
 *
 * O link aponta para GET /{_locale}/api/verify/email (rota
 * VerifyEmailEntryPoint::ROUTE_NAME). O {_locale} vem do contexto do
 * router — o locale da requisição que mandou o email (cadastro ou
 * reenvio).
 *
 * NOTA (out/2026): reescrito.
 * - Implementa a porta do Application; os UseCases não dependem mais
 *   desta classe concreta. Template, assunto e remetente saíram do
 *   RegisterUserUseCase e vieram para cá. Remetente via parâmetros
 *   (app.mailer.sender_address/sender_name em config/services.yaml).
 * - O reenvio usa o template registration/resend_confirmation_email.html.twig,
 *   que existia e ninguém usava.
 * - validateLink() recebe a URL (não o Request): o Application não conhece
 *   HttpFoundation. O Request é recriado aqui para o
 *   validateEmailConfirmationFromRequest() (o validateEmailConfirmation()
 *   com string está deprecated desde o 1.17 do bundle).
 * - Saíram handleEmailConfirmation() (marcar como verificado e salvar é
 *   regra do VerifyEmailUseCase), generateConfirmationSignature() (só o
 *   /api/verify/email/resend usava — e devolvia o link assinado na
 *   resposta HTTP, o que deixava qualquer pessoa verificar o email de
 *   outra) e validateEmailConfirmation() (virou validateLink()).
 * - Falha de envio vira EmailDeliveryException (503). Antes o envio era
 *   chamado dentro de um catch (Exception) no UseCase.
 */
final readonly class EmailVerifier implements EmailVerificationInterface {
    private const string CONFIRMATION_TEMPLATE = 'registration/confirmation_email.html.twig';

    private const string RESEND_TEMPLATE = 'registration/resend_confirmation_email.html.twig';

    /**
     * @param \SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface $verifyEmailHelper
     * @param \Symfony\Component\Mailer\MailerInterface                   $mailer
     * @param \Symfony\Contracts\Translation\TranslatorInterface          $translator
     * @param \Psr\Log\LoggerInterface                                    $logger
     * @param string                                                      $senderAddress
     * @param string                                                      $senderName
     */
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
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
    public function sendConfirmationEmail(User $user, bool $isResend = false): void {
        $signature = $this->verifyEmailHelper->generateSignature(
            routeName: VerifyEmailEntryPoint::ROUTE_NAME,
            userId: (string) $user->getId(),
            userEmail: (string) $user->getEmail(),
            extraParams: ['id' => $user->getId()],
        );

        $email = new TemplatedEmail()
            ->from(new Address($this->senderAddress, $this->senderName))
            ->to((string) $user->getEmail())
            ->subject($this->translator->trans('Please confirm your email address'))
            ->htmlTemplate($isResend ? self::RESEND_TEMPLATE : self::CONFIRMATION_TEMPLATE)
            ->context([
                'user' => $user,
                'app_name' => $this->senderName,
                'signedUrl' => $signature->getSignedUrl(),
                'expiresAtMessageKey' => $signature->getExpirationMessageKey(),
                'expiresAtMessageData' => $signature->getExpirationMessageData(),
            ]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('Failed to send the email confirmation', [
                'user_id' => $user->getId(),
                'is_resend' => $isResend,
                'error' => $exception->getMessage(),
            ]);

            throw new EmailDeliveryException(
                message: 'Failed to send the email confirmation',
                previous: $exception,
                context: ['user_id' => $user->getId()],
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateLink(string $signedUrl, User $user): void {
        try {
            $this->verifyEmailHelper->validateEmailConfirmationFromRequest(
                Request::create($signedUrl),
                (string) $user->getId(),
                (string) $user->getEmail(),
            );
        } catch (VerifyEmailExceptionInterface $exception) {
            throw InvalidVerificationLinkException::fromReason(
                $exception->getReason(),
                (int) $user->getId(),
                $exception instanceof \Exception ? $exception : null,
            );
        }
    }
}
