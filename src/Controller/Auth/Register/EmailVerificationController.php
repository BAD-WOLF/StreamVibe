<?php

namespace App\Controller\Auth\Register;

use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

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
        EmailVerifier $emailVerifier
    ): JsonResponse {
        $id = $request->query->get(key: 'id');

        if (!$id) {
            return $this->json(data: ['error' => 'ID não informado'], status: 400);
        }

        $user = $userRepository->find(id: $id);

        if (!$user) {
            return $this->json(data: ['error' => 'Usuário não encontrado'], status: 404);
        }

        try {
            $emailVerifier->handleEmailConfirmation(request: $request, user: $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            return $this->json(data: [
                'error' => $exception->getReason(),
            ], status: 400);
        }

        return $this->json(data: [
            'message' => 'E-mail verificado com sucesso.',
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
        EmailVerifier $emailVerifier
    ): JsonResponse {
        $data = json_decode(json: $request->getContent(), associative: true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(data: ['error' => 'E-mail não fornecido.'], status: 400);
        }

        $user = $userRepository->findOneBy(criteria: ['email' => $email]);

        if (!$user) {
            return $this->json(data: ['error' => 'Usuário não encontrado.'], status: 404);
        }

        if ($user->isVerified()) {
            return $this->json(data: ['message' => 'E-mail já verificado.'], status: 200);
        }

        // Reenvia o e-mail de verificação
        $emailVerifier->sendEmailConfirmation(
            verifyEmailRouteName: 'api_verify_email',
            user: $user,
            email: new TemplatedEmail()
                ->from(addresses: new Address(address: 'matheusviaira160@gmail.com', name: 'StreamVibe'))
                ->to(addresses: $user->getEmail())
                ->subject(subject: 'Confirme seu e-mail')
                ->htmlTemplate(template: 'registration/confirmation_email.html.twig')
        );

        return $this->json(data: [
            'message' => 'E-mail de verificação reenviado.',
        ]);
    }
}
