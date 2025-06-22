<?php

declare(strict_types = 1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use ApiPlatform\Validator\ValidatorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use App\ApiResource\RegistrationDTO;

#[AsController]
class RegistrationController extends AbstractController {
    /**
     * @param \App\Security\EmailVerifier               $emailVerifier
     * @param \ApiPlatform\Validator\ValidatorInterface $validator
     */
    public function __construct(
        private EmailVerifier $emailVerifier,
        private ValidatorInterface $validator // injetando o validator do API Platform
    ) {
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request                            $request
     * @param \Doctrine\ORM\EntityManagerInterface                                 $em
     * @param \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $hasher
     * @param \Symfony\Component\Serializer\SerializerInterface                    $serializer
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @return \ApiPlatform\Validator\Exception\ValidationException
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    #[Route(name: 'api_register', defaults: [
        '_api_resource_class' => RegistrationDTO::class,
        '_api_operation_name' => 'post_register',
    ], methods: ['POST'])]
    public function __invoke(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        SerializerInterface $serializer
    ): JsonResponse {
        $data = json_decode(json: $request->getContent(), associative: true);

        // 1. Preenche a entidade User com os dados recebidos
        $user = new User();
        $user->setEmail(email: $data['email'] ?? '');
        $user->setPassword(password: $hasher->hashPassword(user: $user, plainPassword: $data['password'] ?? ''));

        // 2. Validação automática pelo API Platform
        //    lança ApiPlatform\Validator\Exception\ValidationException em caso de erro (422)
        $this->validator->validate(data: $user);

        // 3. Persiste o usuário
        $em->persist(object: $user);
        $em->flush();

        // 4. Envia e-mail de confirmação
        $this->emailVerifier->sendEmailConfirmation(
            verifyEmailRouteName: 'api_verify_email',
            user: $user,
            email: new TemplatedEmail()
                ->from(addresses: new Address(address: 'matheusviaira160@gmail.com', name: 'StreamVibe'))
                ->to(addresses: $user->getEmail())
                ->subject(subject: 'Please confirm your email')
                ->htmlTemplate(template: 'registration/confirmation_email.html.twig')
        );

        return $this->json(data: [
            'message' => "Usuário criado com sucesso. Verifique seu e-mail {$user->getEmail()}",
        ], status: 201);
    }
}

