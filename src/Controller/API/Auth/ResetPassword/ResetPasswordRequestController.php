<?php
declare(strict_types = 1);

namespace App\Controller\API\Auth\ResetPassword;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use App\Repository\UserRepository;
use App\ApiResource\Auth\ResetPassword\ResetPasswordEntryPoint;

// 1. Request Password Reset
#[Route(name: 'api_reset_password_request',
    defaults: [
        '_api_resource_class' => ResetPasswordEntryPoint::class,
        '_api_operation_name' => 'post_reset_password_request',
    ]
    , methods: ['POST'])]
class ResetPasswordRequestController extends AbstractController {
    /**
     * @param \SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface $resetPasswordHelper
     */
    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
    ) {
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Repository\UserRepository            $userRepository
     * @param \Symfony\Component\Mailer\MailerInterface $mailer
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function __invoke(Request $request, UserRepository $userRepository, MailerInterface $mailer): JsonResponse {
        $payload = json_decode(json: $request->getContent(), associative: true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(data: ['success' => false, 'error' => 'Invalid JSON payload'], status: 400);
        }

        $email = $payload['email'] ?? null;
        if (!$email) {
            return $this->json(data: ['success' => false, 'error' => 'Email is required'], status: 400);
        }

        $user = $userRepository->findOneBy(criteria: ['email' => $email]);

        if ($user && $user->getId()) {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
            $emailMessage = new TemplatedEmail()
                ->from(new Address('matheusviaira160@gmail.com', 'StreamVibe'))
                ->to($email)
                ->subject('Your password reset request')
                ->htmlTemplate('reset_password/email.html.twig')
                ->context(['resetToken' => $resetToken]);

            $mailer->send($emailMessage);
        }

        return $this->json(data: ['success' => true, 'result' => ['status' => 'reset_email_sent']]);
    }
}
