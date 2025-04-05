<?php

namespace App\Controller {

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\{
    Bridge\Twig\Mime\TemplatedEmail,
    Bundle\FrameworkBundle\Controller\AbstractController,
    Component\HttpFoundation\Request,
    Component\HttpFoundation\Response,
    Component\Mime\Address,
    Component\PasswordHasher\Hasher\UserPasswordHasherInterface,
    Component\Routing\Attribute\Route};

    class RegistrationController extends AbstractController
    {

        /**
         * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
         */
        #[Route('/register', name: 'app_register')]
        public function register(
            Request $request,
            UserPasswordHasherInterface $userPasswordHasher,
            EntityManagerInterface $entityManager,
            EmailVerifier $emailVerifier
        ): Response
        {
            $user = new User();
            $form = $this->createForm(RegistrationFormType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                /** @var string $plainPassword */
                $plainPassword = $form->get('plainPassword')->getData();

                // encode the plain password
                $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

                $entityManager->persist($user);
                $entityManager->flush();

                // generate a signed url and email it to the user
                $emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                    new TemplatedEmail()
                        ->from(new Address('matheu@vieiratechnology.shop', 'StreamVibe'))
                        ->to((string) $user->getEmail())
                        ->subject('Please Confirm your Email')
                        ->htmlTemplate('registration/confirmation_email.html.twig')
                );

                // do anything else you need here, like send an email

                return $this->redirectToRoute('app_login');
            }

            return $this->render('registration/register.html.twig', [
                'registrationForm' => $form,
            ]);
        }
    }
}
