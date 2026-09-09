<?php

declare(strict_types = 1);

namespace App\Application\Authentication\UseCase;

use App\Application\Authentication\DTO\Reset\Request\ResetPasswordRequest;
use App\Application\Authentication\DTO\Reset\Response\ResetPasswordResponse;
use App\Application\Authentication\DTO\Reset\Response\ResetPasswordStatusResponse;
use App\Domain\Exception\ExpiredResetTokenException;
use App\Domain\Exception\InvalidResetTokenException;
use App\Domain\Exception\RateLimitExceededException;
use App\Domain\Exception\ResetPasswordValidationException;
use App\Domain\Exception\ResetTokenNotFoundException;
use App\Domain\Exception\UserNotVerifiedException;
use App\Domain\Authentication\Repository\ResetPasswordSolicitationRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use Exception;
use DateTimeImmutable;
use RuntimeException;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\InvalidResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\Exception\ExpiredResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\Exception\TooManyPasswordRequestsException;
use DateTime;
use Throwable;

/**
 *
 */
final readonly class ResetUserPasswordUseCase {
    /**
     * @param \App\Domain\User\Repository\UserRepositoryInterface                                $userRepository
     * @param \App\Domain\Authentication\Repository\ResetPasswordSolicitationRepositoryInterface $resetPasswordRequestRepository
     * @param \SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface                    $resetPasswordHelper
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface                          $validator
     * @param \Symfony\Component\Mailer\MailerInterface                                          $mailer
     * @param \Psr\Log\LoggerInterface                                                           $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface                                 $translator
     * @param string                                                                             $fromEmail
     * @param string                                                                             $fromName
     */
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private ResetPasswordSolicitationRepositoryInterface $resetPasswordRequestRepository,
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private ValidatorInterface $validator,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        private string $fromEmail = 'noreply@streamvibe.com',
        private string $fromName = 'StreamVibe',
    ) {
    }

    /**
     * @param \App\Application\Authentication\DTO\Reset\Request\ResetPasswordRequest $request
     *
     * @return \App\Application\Authentication\DTO\Reset\Response\ResetPasswordResponse
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function execute(ResetPasswordRequest $request): ResetPasswordResponse {
        // Log inicial
        $this->logger->info(message: 'Starting password reset request', context: [
            'email' => $request->getEmail(),
        ]);

        // Validate input
        $violations = $this->validator->validate(value: $request);
        if (count(value: $violations) > 0) {
            $this->logger->warning(message: 'Validation failed', context: [
                'violations_count' => count(value: $violations),
            ]);

            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }

            throw new ResetPasswordValidationException(
                message: $this->translator->trans(id: 'Invalid reset password request parameters'),
                errors: $errors,
            );
        }

        try {
            // Find user by email
            $this->logger->info(message: 'Finding user by email', context: [
                'email' => $request->getEmail(),
            ]);

            $user = $this->userRepository->findByEmail(email: $request->getEmail());

            if (!$user) {
                $this->logger->info(message: 'User not found - returning generic success', context: [
                    'email' => $request->getEmail(),
                ]);

                return ResetPasswordResponse::created(
                    message: $this->translator->trans(
                        id: 'If an account with this email exists, a password reset link has been sent.'
                    ),
                    emailSent: false,
                );
            }

            $this->logger->info(message: 'User found', context: [
                'user_id' => $user->id,
                'is_verified' => $user->isVerified(),
            ]);

            // Check if user is verified
            if (!$user->isVerified()) {
                $this->logger->warning(message: 'User not verified', context: [
                    'user_id' => $user->id,
                ]);

                throw new UserNotVerifiedException(
                    message: $this->translator->trans(
                        id: 'Please verify your email address before requesting a password reset.'
                    )
                );
            }

            // FIRST: Clean up expired requests BEFORE checking rate limit
            $this->logger->info(message: 'Cleaning up expired requests');
            $this->cleanupExpiredRequests(userId: $user->id);

            // SECOND: Check rate limiting AFTER cleanup
            $this->logger->info(message: 'Checking rate limits');
            $activeRequests = $this->resetPasswordRequestRepository->findActiveRequestsForUser(user: $user);

            $this->logger->info(message: 'Active requests found after cleanup', context: [
                'count' => count(value: $activeRequests),
            ]);

            // Check if there's already a valid request
            if (count(value: $activeRequests) > 0) {
                $existingRequest = $activeRequests[0];

                $this->logger->info(message: 'Found existing active request', context: [
                    'user_id' => $user->id,
                    'request_id' => $existingRequest->getId(),
                    'expires_at' => $existingRequest->getExpiresAt()->format('Y-m-d H:i:s'),
                ]);

                // Convert DateTime to DateTimeImmutable if needed
                $expiresAt = $existingRequest->getExpiresAt();
                if ($expiresAt instanceof DateTime) {
                    $expiresAt = DateTimeImmutable::createFromMutable(object: $expiresAt);
                }

                return ResetPasswordResponse::created(
                    message: $this->translator->trans(
                        id: 'A password reset link has already been sent to your email. Please check your inbox or wait a few minutes before requesting a new one.'
                    ),
                    requestId: (string)$existingRequest->getId(),
                    expiresAt: $expiresAt,
                    emailSent: false, // Not sending new email
                    userId: $user->id,
                );
            }

            // If we have 3 or more requests (shouldn't happen after cleanup, but just in case)
            if (count(value: $activeRequests) >= 3) {
                $this->logger->warning(message: 'Rate limit exceeded', context: [
                    'user_id' => $user->id,
                    'active_requests' => count(value: $activeRequests),
                ]);

                throw new RateLimitExceededException(
                    message: $this->translator->trans(
                        id: 'Too many password reset requests. Please wait before trying again.'
                    )
                );
            }

            // THIRD: Generate reset token only if no active requests exist
            $this->logger->info(message: 'Generating new reset token');
            try {
                $resetToken = $this->resetPasswordHelper->generateResetToken(user: $user);
                $this->logger->info(message: 'Reset token generated successfully', context: [
                    'expires_at' => $resetToken->getExpiresAt()->format(format: 'Y-m-d H:i:s'),
                ]);
            } catch (Throwable $e) {
                $this->logger->error(message: 'Failed to generate reset token', context: [
                    'error' => $e->getMessage(),
                    'error_class' => get_class(object: $e),
                    'user_id' => $user->id,
                    'trace' => $e->getTraceAsString(),
                ]);

                throw new RuntimeException(
                    message: 'Failed to generate password reset token',
                    code: 0,
                    previous: $e
                );
            }

            // Get the reset request entity
            $this->logger->info(message: 'Fetching reset request entity');
            $resetRequestEntity = $this->resetPasswordRequestRepository->findByUser(user: $user);

            if ($resetRequestEntity) {
                $this->logger->info(message: 'Reset request entity found', context: [
                    'request_id' => $resetRequestEntity->getId(),
                ]);
            } else {
                $this->logger->warning(message: 'Reset request entity not found after token generation');
            }

            // Send reset email if requested
            $emailSent = false;
            if ($request->shouldSendEmail()) {
                $this->logger->info(message: 'Attempting to send email');
                $emailSent = $this->sendResetEmail(user: $user, resetToken: $resetToken, request: $request);
                $this->logger->info(message: 'Email send attempt completed', context: [
                    'email_sent' => $emailSent,
                ]);
            }

            $this->logger->info(message: 'Password reset request completed successfully', context: [
                'user_id' => $user->id,
                'email_sent' => $emailSent,
            ]);

            // Convert DateTime to DateTimeImmutable if needed
            $expiresAt = $resetToken->getExpiresAt();
            if ($expiresAt instanceof DateTime) {
                $expiresAt = DateTimeImmutable::createFromMutable(object: $expiresAt);
            }

            return ResetPasswordResponse::created(
                message: $this->translator->trans(id: 'A password reset link has been sent to your email address.'),
                requestId: $resetRequestEntity?->getId() ? (string)$resetRequestEntity->getId() : null,
                expiresAt: $expiresAt,
                emailSent: $emailSent,
                userId: $user->id,
            );

        } catch (UserNotVerifiedException|RateLimitExceededException $e) {
            $this->logger->warning(message: 'Expected exception caught', context: [
                'exception_class' => get_class(object: $e),
                'message' => $e->getMessage(),
            ]);
            throw $e;
        } catch (Exception $e) {
            $this->logger->error(message: 'Unexpected error processing password reset request', context: [
                'email' => $request->getEmail(),
                'error' => $e->getMessage(),
                'error_class' => get_class(object: $e),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new RuntimeException(
                message: $this->translator->trans(
                    id: 'An error occurred while processing your password reset request.'
                ),
                code: 0,
                previous: $e,
            );
        }
    }

    /**
     * @throws InvalidResetTokenException
     * @throws ExpiredResetTokenException
     * @throws ResetTokenNotFoundException
     */
    /**
     * @throws InvalidResetTokenException
     * @throws ExpiredResetTokenException
     * @throws ResetTokenNotFoundException
     */
    public function checkStatus(string $token): ResetPasswordStatusResponse {
        $this->logger->info('=== TOKEN STATUS CHECK STARTED ===', [
            'token_length' => strlen($token),
            'token_prefix' => substr($token, 0, 10).'...',
            'token_suffix' => '...'.substr($token, -10),
        ]);

        // Validate token format
        if (empty($token)) {
            $this->logger->error('VALIDATION FAILED: Empty token');
            throw InvalidResetTokenException::invalidFormat('');
        }

        if (strlen($token) < 20) {
            $this->logger->error('VALIDATION FAILED: Token too short', [
                'length' => strlen($token),
                'expected_min' => 20,
            ]);
            throw InvalidResetTokenException::invalidFormat($token);
        }

        try {
            $this->logger->info('STEP 1: Validating token with ResetPasswordHelper');

            // This may throw SymfonyCasts exceptions
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser(fullToken: $token);

            if (!$user) {
                $this->logger->error('STEP 1 FAILED: validateTokenAndFetchUser returned null');
                throw ResetTokenNotFoundException::byToken($token);
            }

            $this->logger->info('STEP 1 SUCCESS: User found', [
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail(),
            ]);

            // STEP 2: Get reset request from repository
            $this->logger->info('STEP 2: Fetching reset request from repository');
            $resetRequest = $this->resetPasswordRequestRepository->findByUser(user: $user);
            if (!$resetRequest) {
                $this->logger->error('STEP 2 FAILED: No reset request in database', [
                    'user_id' => $user->getId(),
                ]);
                throw ResetTokenNotFoundException::byUser($user->getId());
            }

            $this->logger->info('STEP 2 SUCCESS: Reset request found: request_id={request_id}', [
                'request_id' => $resetRequest->getId(),
                'requested_at' => $resetRequest->getRequestedAt()->format('Y-m-d H:i:s'),
                'expires_at' => $resetRequest->getExpiresAt()->format('Y-m-d H:i:s'),
            ]);

            // STEP 3: Check if expired
            $this->logger->info('STEP 3: Checking expiration');
            $isExpired = $resetRequest->isExpired();
            $now = new DateTimeImmutable();

            $this->logger->info('STEP 3 RESULT: Expiration check', [
                'is_expired' => $isExpired,
                'expires_at' => $resetRequest->getExpiresAt()->format('Y-m-d H:i:s'),
                'current_time' => $now->format('Y-m-d H:i:s'),
                'time_remaining' => $isExpired ? 'expired' : $resetRequest->getExpiresAt()->diff($now)->format(
                    '%h hours %i minutes'
                ),
            ]);

            if ($isExpired) {
                $this->logger->warning('STEP 3: Token is expired');
                throw ExpiredResetTokenException::create($resetRequest->getExpiresAt());
            }

            // Convert DateTime to DateTimeImmutable if needed
            $expiresAt = $resetRequest->getExpiresAt();
            if ($expiresAt instanceof DateTime) {
                $expiresAt = DateTimeImmutable::createFromMutable($expiresAt);
            }

            $this->logger->info('=== TOKEN STATUS CHECK COMPLETED SUCCESSFULLY ===');

            return ResetPasswordStatusResponse::valid(
                message: $this->translator->trans(id: 'Reset token is valid'),
                expired: false,
                expiresAt: $expiresAt,
            );

        } catch (ResetTokenNotFoundException|ExpiredResetTokenException $e) {
            $this->logger->warning('Expected exception during token validation', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'reason' => method_exists($e, 'getReason') ? $e->reason : null,
                'context' => method_exists($e, 'getContext') ? $e->context : [],
            ]);
            throw $e;

        } catch (TooManyPasswordRequestsException $e) {
            $this->logger->error('BUNDLE ERROR: Too many password requests', [
                'message' => $e->getMessage(),
                'available_at' => method_exists($e, 'getAvailableAt') ? $e->getAvailableAt()->format(
                    'Y-m-d H:i:s'
                ) : 'unknown',
            ]);
            throw InvalidResetTokenException::bundleError($e);

        } catch (ExpiredResetPasswordTokenException $e) {
            $this->logger->error('BUNDLE ERROR: Token expired (from bundle)', [
                'message' => $e->getMessage(),
            ]);
            throw ExpiredResetTokenException::create(new DateTimeImmutable());

        } catch (InvalidResetPasswordTokenException $e) {
            $this->logger->error('BUNDLE ERROR: Invalid token (from bundle)', [
                'message' => $e->getMessage(),
            ]);
            throw InvalidResetTokenException::bundleError($e);

        } catch (ResetPasswordExceptionInterface $e) {
            $this->logger->error('BUNDLE ERROR: Generic reset password exception', [
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'reason' => method_exists($e, 'getReason') ? $e->getReason() : 'unknown',
            ]);
            throw InvalidResetTokenException::bundleError($e);

        } catch (Exception $e) {
            $this->logger->error('UNEXPECTED ERROR during token validation', [
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString()),
            ]);
            throw InvalidResetTokenException::unexpectedError($e);
        }
    }

    /**
     * @param string $token
     *
     * @return \App\Application\Authentication\DTO\Reset\Response\ResetPasswordResponse
     * @throws \App\Domain\Exception\ResetTokenNotFoundException
     */
    public function cancel(string $token): ResetPasswordResponse {
        try {
            // NOTA: validateTokenAndFetchUser() nunca retorna null — a
            // interface declara retorno "object" não-nullable e sinaliza
            // falha de validação via exceção do bundle, não via null
            // (mesmo raciocínio já confirmado e aplicado em checkStatus()).
            // O "if (!$user)" antigo aqui era código morto.
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser(fullToken: $token);

            // Remove the reset request
            $this->resetPasswordHelper->removeResetRequest(fullToken: $token);

            $this->logger->info(message: 'Password reset request cancelled', context: [
                'user_id' => $user->getId(),
            ]);

            return ResetPasswordResponse::cancelled(
                message: $this->translator->trans(id: 'Password reset request has been cancelled.'),
            );

        } catch (ResetTokenNotFoundException $e) {
            throw $e;
        } catch (ResetPasswordExceptionInterface $e) {
            // NOTA (bug corrigido, ago/2026): antes, qualquer exceção do
            // bundle para token inválido/inexistente/expirado caía direto
            // no catch(Exception) genérico logo abaixo e virava sempre 500
            // — mesmo o controller já sabendo mapear
            // ResetTokenNotFoundException para 404. "Cancelar token
            // inexistente" nunca respondia 404 por causa disso. Mesmo
            // padrão de mapeamento já usado em checkStatus() para as
            // mesmas exceções do bundle.
            $this->logger->warning(message: 'Bundle error cancelling reset request', context: [
                'error' => $e->getMessage(),
                'error_class' => get_class(object: $e),
            ]);

            throw ResetTokenNotFoundException::byToken($token);
        } catch (Exception $e) {
            $this->logger->error(message: 'Error cancelling reset request', context: [
                'token' => substr(string: $token, offset: 0, length: 8).'...',
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                message: $this->translator->trans(id: 'Error cancelling reset request'),
                code: 0,
                previous: $e,
            );
        }
    }

    /**
     * @param int $userId
     *
     * @return array
     */
    public function getUserActiveRequests(int $userId): array {
        try {
            $user = $this->userRepository->findById(id: $userId);

            if (!$user) {
                return [];
            }

            $requests = $this->resetPasswordRequestRepository->findActiveRequestsForUser(user: $user);

            return array_map(callback: function ($request) {
                return [
                    'id' => $request->getId(),
                    'created_at' => $request->getRequestedAt(),
                    'expires_at' => $request->getExpiresAt(),
                ];
            }, array: $requests);

        } catch (Exception $e) {
            $this->logger->error(message: 'Error fetching user reset requests', context: [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @param                                                                $user
     * @param                                                                $resetToken
     * @param \App\Application\Authentication\DTO\Reset\Request\ResetPasswordRequest $request
     *
     * @return bool
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    private function sendResetEmail($user, $resetToken, ResetPasswordRequest $request): bool {
        try {
            $email = (new TemplatedEmail())
                ->from(new Address(address: $this->fromEmail, name: $this->fromName))
                ->to($user->getEmail())
                ->subject(subject: $this->translator->trans(id: 'Password Reset Request - StreamVibe'))
                ->htmlTemplate(template: 'reset_password/email.html.twig')
                ->context(context: [
                    'user' => $user,
                    'resetToken' => $resetToken,
                    'tokenLifetime' => $resetToken->getExpirationMessageData()['%count%'] ?? 60,
                    'app_name' => 'StreamVibe',
                    'request_info' => [
                        'ip_address' => $request->getIpAddress(),
                        'user_agent' => $request->getUserAgent(),
                        'timestamp' => new DateTimeImmutable(),
                    ],
                ]);

            $this->mailer->send(message: $email);

            $this->logger->info(message: 'Password reset email sent successfully', context: [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
            ]);

            return true;

        } catch (Exception $e) {
            $this->logger->error(message: 'Failed to send password reset email', context: [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param int|null $userId
     *
     * @return void
     */
    private function cleanupExpiredRequests(?int $userId = null): void {
        try {
            if ($userId) {
                $user = $this->userRepository->findById(id: $userId);
                if ($user) {
                    $allRequests = $this->resetPasswordRequestRepository->findActiveRequestsForUser(user: $user);

                    $this->logger->info(message: 'Checking requests for cleanup', context: [
                        'user_id' => $userId,
                        'total_requests' => count(value: $allRequests),
                    ]);

                    foreach ($allRequests as $request) {
                        if ($request->isExpired()) {
                            $this->logger->info(message: 'Removing expired request', context: [
                                'request_id' => $request->getId(),
                                'expired_at' => $request->getExpiresAt()->format('Y-m-d H:i:s'),
                            ]);
                            $this->resetPasswordRequestRepository->remove(resetPasswordRequest: $request, flush: true);
                        }
                    }
                }
            } else {
                $removedCount = $this->resetPasswordRequestRepository->removeExpiredRequests();

                if ($removedCount > 0) {
                    $this->logger->info(message: 'Cleaned up expired reset requests', context: [
                        'removed_count' => $removedCount,
                    ]);
                }
            }

        } catch (Exception $e) {
            $this->logger->error(message: 'Error cleaning up expired reset requests', context: [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
