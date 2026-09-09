<?php
declare(strict_types = 1);

namespace App\Infrastructure\Security;

use App\Domain\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 *
 */
readonly class EmailVerifier {
    /**
     * @param \SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface $verifyEmailHelper
     * @param \Symfony\Component\Mailer\MailerInterface                   $mailer
     * @param \Doctrine\ORM\EntityManagerInterface                        $entityManager
     */
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendEmailConfirmation(
        string $verifyEmailRouteName,
        User $user,
        TemplatedEmail $email,
    ): void {
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            routeName: $verifyEmailRouteName,
            userId: (string)$user->id,
            userEmail: (string)$user->getEmail(),
            extraParams: ['id' => $user->id],
        );

        $context = $email->getContext();
        $context['signedUrl'] = $signatureComponents->getSignedUrl();
        $context['expiresAtMessageKey'] = $signatureComponents->getExpirationMessageKey();
        $context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData();

        $email->context($context);

        $this->mailer->send($email);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Domain\User\Entity\User              $user
     */
    public function handleEmailConfirmation(Request $request, User $user): void {
        $this->verifyEmailHelper->validateEmailConfirmationFromRequest(
            $request,
            (string)$user->id,
            (string)$user->getEmail(),
        );

        $user->setIsVerified(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * Generate email confirmation signature for user
     */
    public function generateConfirmationSignature(
        string $verifyEmailRouteName,
        User $user,
        array $extraParams = [],
    ): array {
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $verifyEmailRouteName,
            (string)$user->id,
            (string)$user->getEmail(),
            array_merge(['id' => $user->id], $extraParams),
        );

        return [
            'signedUrl' => $signatureComponents->getSignedUrl(),
            'expiresAtMessageKey' => $signatureComponents->getExpirationMessageKey(),
            'expiresAtMessageData' => $signatureComponents->getExpirationMessageData(),
        ];
    }

    /**
     * Validate email confirmation without marking user as verified
     */
    public function validateEmailConfirmation(
        Request $request,
        User $user,
    ): bool {
        try {
            $this->verifyEmailHelper->validateEmailConfirmationFromRequest(
                $request,
                (string)$user->id,
                (string)$user->getEmail(),
            );

            return true;
        } catch (VerifyEmailExceptionInterface) {
            return false;
        }
    }
}
