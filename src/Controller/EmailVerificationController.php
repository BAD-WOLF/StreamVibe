<?php

namespace App\Controller {

    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\Component\HttpFoundation\Request;
    use App\Repository\UserRepository;
    use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
    use App\Security\EmailVerifier;
    use App\Form\ReverifyFormType;
    use Symfony\Bridge\Twig\Mime\TemplatedEmail;
    use Symfony\Component\Mime\Address;

    class EmailVerificationController extends AbstractController {
        #[Route('/verify/email', name: 'app_verify_email')]
        public function verifyUserEmail(
            Request $request, UserRepository $userRepository, EmailVerifier $emailVerifier
        ): Response {
            $id = $request->query->get('id');

            if(null === $id) {
                return $this->redirectToRoute('app_register');
            }

            $user = $userRepository->find($id);

            if(null === $user) {
                return $this->redirectToRoute('app_register');
            }

            // validate email confirmation link, sets User::isVerified=true and persists
            try {
                $emailVerifier->handleEmailConfirmation($request, $user);
            } catch(VerifyEmailExceptionInterface $exception) {
                $this->addFlash('verify_email_error', $exception->getReason());

                return $this->redirectToRoute('app_register');
            }

            // @TODO Change the redirect on success and handle or remove the flash message in your templates
            $this->addFlash('success', 'Your email address has been verified.');

            return $this->render('registration/verified_email.html.twig');
        }

        /**
         * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
         */
        #[Route('/resend/verify/email', name: 'app_reverify_email')]
        public function reverifyUserEmail(Request $request, UserRepository $userRepository, EmailVerifier $emailVerifier): Response {
            if($this->getUser()) {
                $this->redirectToRoute('home');
            }

            $form = $this->createForm(ReverifyFormType::class);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // Retrieve user by email from the form
                $user = $userRepository->findOneBy([
                    "email" => $form->get(name: 'email')->getData()
                ]);

                if ($user && !$user->isVerified()) {
                    // If user exists and is not verified, send confirmation email
                    $emailVerifier->sendEmailConfirmation(
                        "app_verify_email",
                        $user,
                        new TemplatedEmail()
                            ->from(new Address("matheu@vieiratechnology.shop", 'StreamVibe'))
                            ->to($user->getEmail())
                            ->subject("StreamVibe")
                            ->htmlTemplate('registration/resend_confirmation_email.html.twig')
                    );
                    $this->render('registration/verified_email.html.twig');
                } else if (!$user) {
                    // If user doesn't exist, show error and reload the form
                    $this->addFlash('error', 'Incorrect Email!!');
                    return $this->redirectToRoute('app_reverify_email');
                } else if ($user->isVerified()) {
                    // If user is already verified, redirect to login with error
                    $this->addFlash("error", "User Email Already Verified!!");
                    return $this->redirectToRoute('app_login');
                }
            }

            // Render the reverify form if not submitted or invalid
            return $this->render('registration/reverify.html.twig', [
                'ReverifyForm' => $form
            ]);

        }
    }
}
