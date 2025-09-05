<?php

namespace App\Controller\API\Auth\Register;

use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailVerificationController extends AbstractController {
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Repository\UserRepository            $userRepository
     * @param \App\Security\EmailVerifier               $emailVerifier
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    #[Route(path: '/api/verify/email', name: 'api_verify_email', methods: ['GET'])]
    public function verifyUserEmail(
        Request $request,
        UserRepository $userRepository,
        EmailVerifier $emailVerifier,
        TranslatorInterface $translator
    ): JsonResponse {
        $id = $request->query->get(key: 'id');

        if (!$id) {
            return $this->json(data: ['error' => $translator->trans('ID not informed')], status: 400);
        }

        $user = $userRepository->find(id: $id);

        if (!$user) {
            return $this->json(data: ['error' => $translator->trans('User not found')], status: 404);
        }

        try {
            $emailVerifier->handleEmailConfirmation(request: $request, user: $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            return $this->json(data: [
                'error' => $exception->getReason(),
            ], status: 400);
        }

        return $this->json(data: [
            'message' => $translator->trans('E-mail verified successfully.'),
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Repository\UserRepository            $userRepository
     * @param \App\Security\EmailVerifier               $emailVerifier
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    #[Route(path: '/api/resend/verify/email', name: 'api_reverify_email', methods: ['POST'])]
    public function reverifyUserEmail(
        Request $request,
        UserRepository $userRepository,
        EmailVerifier $emailVerifier,
        TranslatorInterface $translator
    ): JsonResponse {
        $data = json_decode(json: $request->getContent(), associative: true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(data: ['error' => $translator->trans('E-mail not provided.')], status: 400);
        }

        $user = $userRepository->findOneBy(criteria: ['email' => $email]);

        if (!$user) {
            return $this->json(data: ['error' => $translator->trans('User not found.')], status: 404);
        }

        if ($user->isVerified()) {
            return $this->json(data: ['message' => $translator->trans('E-mail already verified.')], status: 200);
        }

        // Resend the verification email
        $emailVerifier->sendEmailConfirmation(
            verifyEmailRouteName: 'api_verify_email',
            user: $user,
            email: new TemplatedEmail()
                ->from(addresses: new Address(address: 'matheusviaira160@gmail.com', name: 'StreamVibe'))
                ->to(addresses: $user->getEmail())
                ->subject(subject: $translator->trans('Confirm your email'))
                ->htmlTemplate(template: 'registration/confirmation_email.html.twig')
        );

        return $this->json(data: [
            'message' => $translator->trans('Verification email resent.'),
        ]);
    }
}
