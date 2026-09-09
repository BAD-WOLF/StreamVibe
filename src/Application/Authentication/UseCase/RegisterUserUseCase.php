<?php

declare(strict_types = 1);

namespace App\Application\Authentication\UseCase;

use App\Application\Authentication\DTO\Register\RegisterUserRequest;
use App\Application\Authentication\DTO\Register\RegisterUserResponse;
use App\Domain\User\Entity\User;
use App\Domain\Exception\EmailDeliveryException;
use App\Domain\Exception\UserAlreadyExistsException;
use App\Domain\Exception\UserNotFoundException;
use App\Domain\Exception\UserValidationException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Exception;
use RuntimeException;

/**
 *
 */
final readonly class RegisterUserUseCase {
    /**
     * @param \App\Domain\User\Repository\UserRepositoryInterface                  $userRepository
     * @param \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher
     * @param \App\Infrastructure\Security\EmailVerifier                           $emailVerifier
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface            $validator
     * @param \Symfony\Contracts\Translation\TranslatorInterface                   $translator
     */
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EmailVerifier $emailVerifier,
        private ValidatorInterface $validator,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws UserValidationException
     * @throws \App\Domain\Exception\UserAlreadyExistsException
     * @throws UserNotFoundException
     * @throws EmailDeliveryException
     * @throws \RuntimeException
     */
    public function execute(RegisterUserRequest $request): RegisterUserResponse {
        // Handle resend verification
        if ($request->isResendVerification()) {
            return $this->handleResendVerification(request: $request);
        }

        // Validate input data
        $violations = $this->validator->validate(value: $request);
        if (count(value: $violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }

            throw new UserValidationException(
                message: $this->translator->trans(id: 'Validation failed'),
                errors: $errors,
            );
        }

        // Check if user already exists
        $existingUser = $this->userRepository->findByEmail(
            email: $request->getEmail(),
        );
        if ($existingUser !== null) {
            if ($existingUser->isVerified()) {
                throw new UserAlreadyExistsException(
                    message: $this->translator->trans(
                        id: 'A user with this email already exists',
                    ),
                );
            }

            // User exists but not verified, resend verification
            return $this->sendVerificationEmail(user: $existingUser);
        }

        // Create new user
        $user = new User();
        $user->setEmail(email: $request->getEmail());
        $user->setPassword(
            password: $this->passwordHasher->hashPassword(user: $user, plainPassword: $request->getPassword()),
        );
        $user->setIsVerified(isVerified: false);

        // Validate user entity
        $userViolations = $this->validator->validate(value: $user);
        if (count(value: $userViolations) > 0) {
            $errors = [];
            foreach ($userViolations as $violation) {
                $errors[] = $violation->getMessage();
            }

            throw new UserValidationException(
                message: $this->translator->trans(id: 'User validation failed'),
                errors: $errors,
            );
        }

        // Persist user
        try {
            $this->userRepository->save(user: $user, flush: true);
        } catch (Exception $e) {
            throw new RuntimeException(
                message: $this->translator->trans(id: 'Failed to save user: {error}', parameters: [
                    'error' => $e->getMessage(),
                ]),
                code: 0,
                previous: $e,
            );
        }

        // Send verification email
        $emailResponse = $this->sendVerificationEmail(user: $user);

        return RegisterUserResponse::success(
            message: $this->translator->trans(
                id: 'User registered successfully. Please check your email to verify your account.',
            ),
            userId: $user->id,
            emailSent: $emailResponse->wasEmailSent(),
            verificationUrl: $emailResponse->getVerificationUrl(),
        );
    }

    /**
     * @throws \App\Domain\Exception\UserNotFoundException
     * @throws \App\Domain\Exception\UserAlreadyExistsException
     * @throws EmailDeliveryException
     */
    private function handleResendVerification(
        RegisterUserRequest $request,
    ): RegisterUserResponse {
        $user = $this->userRepository->findByEmail(email: $request->getEmail());

        if ($user === null) {
            throw new UserNotFoundException(
                message: $this->translator->trans(
                    id: 'No user found with this email address',
                ),
            );
        }

        if ($user->isVerified()) {
            throw new UserAlreadyExistsException(
                message: $this->translator->trans(id: 'This account is already verified'),
            );
        }

        return $this->sendVerificationEmail(user: $user);
    }

    /**
     * @param \App\Domain\User\Entity\User $user
     *
     * @return \App\Application\Authentication\DTO\Register\RegisterUserResponse
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    private function sendVerificationEmail(User $user): RegisterUserResponse {
        try {
            $email = new TemplatedEmail()
                ->from(new Address(address: 'noreply@streamvibe.com', name: 'StreamVibe'))
                ->to($user->getEmail())
                ->subject(
                    subject: $this->translator->trans(
                        id: 'Please confirm your email address',
                    ),
                )
                ->htmlTemplate(template: 'registration/confirmation_email.html.twig')
                ->context(context: [
                    'user' => $user,
                    'app_name' => 'StreamVibe',
                ]);

            $this->emailVerifier->sendEmailConfirmation(
                verifyEmailRouteName: 'api_verify_email',
                user: $user,
                email: $email,
            );

            return RegisterUserResponse::emailResent(
                message: $this->translator->trans(
                    id: 'Verification email has been sent. Please check your inbox.',
                ),
                userId: $user->id,
            );
        } catch (Exception $e) {
            throw new EmailDeliveryException(
                message: $this->translator->trans(
                    id: 'Failed to send verification email: {error}',
                    parameters: [
                        'error' => $e->getMessage(),
                    ],
                ),
                previous: $e,
            );
        }
    }
}