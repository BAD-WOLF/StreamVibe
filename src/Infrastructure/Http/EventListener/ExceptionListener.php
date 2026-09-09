<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\EventListener;

use App\Domain\Exception\ValidationException;
use App\Domain\Exception\BusinessLogicException;
use App\Domain\Exception\ExternalServiceException;
use App\Domain\Exception\DomainException;
use App\Domain\Exception\UserValidationException;
use App\Domain\Exception\UserNotFoundException;
use App\Domain\Exception\UserNotVerifiedException;
use App\Domain\Exception\UserAlreadyExistsException;
use App\Domain\Exception\EmailDeliveryException;
use App\Domain\Exception\RateLimitExceededException;
use App\Domain\Exception\InvalidResetTokenException;
use App\Domain\Exception\ExpiredResetTokenException;
use App\Domain\Exception\ResetTokenNotFoundException;
use App\Domain\Exception\ResetPasswordValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 *
 */
readonly class ExceptionListener
{
    /**
     * @param \Psr\Log\LoggerInterface                           $logger
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator
     * @param bool                                               $debug
     */
    public function __construct(
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        private bool $debug = false,
    ) {}

    /**
     * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
     *
     * @return void
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Only handle API requests (those starting with /api, matched to an
        // API Platform resource, or having JSON content type)
        $request = $event->getRequest();

        // NOTA: str_starts_with(pathInfo, "/api") sozinho falha para rotas
        // com prefixo de locale, ex.: "/pt_BR/api/movies/search/...".
        // Isso fazia esse listener não interceptar exceções nessas rotas
        // (isApiRequest ficava false quando a requisição também não tinha
        // Content-Type/Accept "application/json" explícitos, como em GETs
        // simples), deixando o próprio ApiPlatform devolver seu erro
        // genérico "application/problem+json" com 500 em vez do código
        // correto (ex.: 400 pra UserValidationException). A checagem do
        // atributo de rota "_api_resource_class" é a forma confiável de
        // identificar uma rota API Platform, independente do path.
        $isApiRequest =
            str_starts_with($request->getPathInfo(), "/api") ||
            str_contains($request->getPathInfo(), "/api/") ||
            $request->attributes->get("_api_resource_class") !== null ||
            str_contains(
                $request->headers->get("Content-Type", ""),
                "application/json",
            ) ||
            str_contains(
                $request->headers->get("Accept", ""),
                "application/json",
            );

        if (!$isApiRequest) {
            return;
        }

        $response = null;

        // Handle specific domain exceptions first
        if ($exception instanceof UserValidationException) {
            $response = $this->handleUserValidationException($exception);
        } elseif ($exception instanceof UserNotFoundException) {
            $response = $this->handleUserNotFoundException($exception);
        } elseif ($exception instanceof UserNotVerifiedException) {
            $response = $this->handleUserNotVerifiedException($exception);
        } elseif ($exception instanceof UserAlreadyExistsException) {
            $response = $this->handleUserAlreadyExistsException($exception);
        } elseif ($exception instanceof EmailDeliveryException) {
            $response = $this->handleEmailDeliveryException($exception);
        } elseif ($exception instanceof RateLimitExceededException) {
            $response = $this->handleRateLimitExceededException($exception);
        } elseif ($exception instanceof InvalidResetTokenException) {
            $response = $this->handleInvalidResetTokenException($exception);
        } elseif ($exception instanceof ExpiredResetTokenException) {
            $response = $this->handleExpiredResetTokenException($exception);
        } elseif ($exception instanceof ResetTokenNotFoundException) {
            $response = $this->handleResetTokenNotFoundException($exception);
        } elseif ($exception instanceof ResetPasswordValidationException) {
            $response = $this->handleResetPasswordValidationException(
                $exception,
            );
        } elseif ($exception instanceof ValidationException) {
            $response = $this->handleValidationException($exception);
        } elseif ($exception instanceof BusinessLogicException) {
            $response = $this->handleBusinessLogicException($exception);
        } elseif ($exception instanceof ExternalServiceException) {
            $response = $this->handleExternalServiceException($exception);
        } elseif ($exception instanceof DomainException) {
            $response = $this->handleDomainException($exception);
        } elseif ($exception instanceof HttpException) {
            $response = $this->handleHttpException($exception);
        } else {
            $response = $this->handleGenericException($exception);
        }

        if ($response) {
            $event->setResponse($response);
        }
    }

    /**
     * @param \App\Domain\Exception\ValidationException $exception
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    private function handleValidationException(
        ValidationException $exception,
    ): JsonResponse {
        $this->logger->warning("Validation exception occurred", [
            "message" => $exception->getMessage(),
            "errors" => $exception->errors,
            "context" => $exception->context,
            "trace" => $this->debug ? $exception->getTraceAsString() : null,
        ]);

        $data = [
            "success" => false,
            "error" => [
                "type" => $exception->getErrorType(),
                "message" => $exception->getMessage(),
                "errors" => $exception->errors,
            ],
        ];

        if ($this->debug) {
            $data["debug"] = [
                "context" => $exception->context,
                "file" => $exception->getFile(),
                "line" => $exception->getLine(),
                "trace" => explode("\n", $exception->getTraceAsString()),
            ];
        }

        return new JsonResponse($data, $exception->getCode() ?: 400);
    }

    /**
     * @param \App\Domain\Exception\BusinessLogicException $exception
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    private function handleBusinessLogicException(
        BusinessLogicException $exception,
    ): JsonResponse {
        $this->logger->warning("Business logic exception occurred", [
            "message" => $exception->getMessage(),
            "context" => $exception->context,
            "trace" => $this->debug ? $exception->getTraceAsString() : null,
        ]);

        $data = [
            "success" => false,
            "error" => [
                "type" => $exception->getErrorType(),
                "message" => $exception->getMessage(),
            ],
        ];

        if ($this->debug) {
            $data["debug"] = [
                "context" => $exception->context,
                "file" => $exception->getFile(),
                "line" => $exception->getLine(),
                "trace" => explode("\n", $exception->getTraceAsString()),
            ];
        }

        return new JsonResponse($data, $exception->getCode() ?: 422);
    }

    /**
     * @param \App\Domain\Exception\ExternalServiceException $exception
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    private function handleExternalServiceException(
        ExternalServiceException $exception,
    ): JsonResponse {
        $this->logger->error("External service exception occurred", [
            "message" => $exception->getMessage(),
            "context" => $exception->context,
            "trace" => $exception->getTraceAsString(),
        ]);

        $data = [
            "success" => false,
            "error" => [
                "type" => $exception->getErrorType(),
                "message" => $this->translator->trans(
                    "External service temporarily unavailable. Please try again later.",
                ),
            ],
        ];

        if ($this->debug) {
            $data["debug"] = [
                "original_message" => $exception->getMessage(),
                "context" => $exception->context,
                "file" => $exception->getFile(),
                "line" => $exception->getLine(),
                "trace" => explode("\n", $exception->getTraceAsString()),
            ];
        }

        return new JsonResponse($data, $exception->getCode() ?: 502);
    }

    /**
     * @param \App\Domain\Exception\DomainException $exception
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    private function handleDomainException(
        DomainException $exception,
    ): JsonResponse {
        $this->logger->error("Domain exception occurred", [
            "message" => $exception->getMessage(),
            "context" => $exception->context,
            "trace" => $exception->getTraceAsString(),
        ]);

        $data = [
            "success" => false,
            "error" => [
                "type" => $exception->getErrorType(),
                "message" => $exception->getMessage(),
            ],
        ];

        if ($this->debug) {
            $data["debug"] = [
                "context" => $exception->context,
                "file" => $exception->getFile(),
                "line" => $exception->getLine(),
                "trace" => explode("\n", $exception->getTraceAsString()),
            ];
        }

        return new JsonResponse($data, $exception->getCode() ?: 500);
    }

    /**
     * @param \Symfony\Component\HttpKernel\Exception\HttpException $exception
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    private function handleHttpException(HttpException $exception): JsonResponse
    {
        $this->logger->warning("HTTP exception occurred", [
            "status_code" => $exception->getStatusCode(),
            "message" => $exception->getMessage(),
            "headers" => $exception->getHeaders(),
            "trace" => $this->debug ? $exception->getTraceAsString() : null,
        ]);

        $data = [
            "success" => false,
            "error" => [
                "type" => "http_error",
                "message" =>
                    $exception->getMessage() ?: "An HTTP error occurred",
            ],
        ];

        if ($this->debug) {
            $data["debug"] = [
                "headers" => $exception->getHeaders(),
                "file" => $exception->getFile(),
                "line" => $exception->getLine(),
                "trace" => explode("\n", $exception->getTraceAsString()),
            ];
        }

        return new JsonResponse($data, $exception->getStatusCode());
    }

    /**
     * @param \Throwable $exception
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    private function handleGenericException(Throwable $exception): JsonResponse
    {
        $this->logger->critical("Unhandled exception occurred", [
            "message" => $exception->getMessage(),
            "class" => get_class($exception),
            "file" => $exception->getFile(),
            "line" => $exception->getLine(),
            "trace" => $exception->getTraceAsString(),
        ]);

        $data = [
            "success" => false,
            "error" => [
                "type" => "internal_error",
                "message" => $this->translator->trans(
                    "An internal error occurred. Please try again later.",
                ),
            ],
        ];

        if ($this->debug) {
            $data["debug"] = [
                "original_message" => $exception->getMessage(),
                "class" => get_class($exception),
                "file" => $exception->getFile(),
                "line" => $exception->getLine(),
                "trace" => explode("\n", $exception->getTraceAsString()),
                "memory_usage" => memory_get_usage(true),
                "peak_memory" => memory_get_peak_usage(true),
            ];
        }

        return new JsonResponse($data, 500);
    }

    private function handleUserValidationException(
        UserValidationException $exception,
    ): JsonResponse {
        $this->logger->warning("User validation exception occurred", [
            "message" => $exception->getMessage(),
            "errors" => $exception->getErrors(),
            "context" => $exception->getContext(),
            "trace" => $this->debug ? $exception->getTraceAsString() : null,
        ]);

        $data = [
            "success" => false,
            "error" => [
                "type" => $exception->getErrorType(),
                "message" => $exception->getMessage(),
                "errors" => $exception->getErrors(),
            ],
        ];

        if ($this->debug) {
            $data["debug"] = [
                "context" => $exception->getContext(),
                "file" => $exception->getFile(),
                "line" => $exception->getLine(),
                "trace" => explode("\n", $exception->getTraceAsString()),
            ];
        }

        return new JsonResponse($data, $exception->getCode() ?: 400);
    }

    private function handleUserNotFoundException(
        UserNotFoundException $exception,
    ): JsonResponse {
        $this->logger->info("User not found exception occurred", [
            "message" => $exception->getMessage(),
            "context" => $exception->getContext(),
        ]);

        $data = [
            "success" => false,
            "error" => [
                "type" => $exception->getErrorType(),
                "message" => $exception->getMessage(),
            ],
        ];

        if ($this->debug) {
            $data["debug"] = [
                "context" => $exception->getContext(),
                "file" => $exception->getFile(),
                "line" => $exception->getLine(),
            ];
        }

        return new JsonResponse($data, $exception->getCode() ?: 404);
    }

    private function handleUserNotVerifiedException(
        UserNotVerifiedException $exception,
    ): JsonResponse {
        $this->logger->warning("User not verified exception occurred", [
            "message" => $exception->getMessage(),
            "context" => $exception->getContext(),
        ]);

        $data = [
            "success" => false,
            "error" => [
                "type" => $exception->getErrorType(),
                "message" => $exception->getMessage(),
                "action_required" => "email_verification",
            ],
        ];

        if ($this->debug) {
            $data["debug"] = [
                "context" => $exception->getContext(),
                "file" => $exception->getFile(),
                "line" => $exception->getLine(),
            ];
        }

        return new JsonResponse($data, $exception->getCode() ?: 403);
    }

    private function handleUserAlreadyExistsException(
        UserAlreadyExistsException $exception,
    ): JsonResponse {
        $this->logger->info("User already exists exception occurred", [
            "message" => $exception->getMessage(),
            "context" => $exception->getContext(),
        ]);

        $data = [
            "success" => false,
            "error" => [
                "type" => $exception->getErrorType(),
                "message" => $exception->getMessage(),
            ],
        ];

        if ($this->debug) {
            $data["debug"] = [
                "context" => $exception->getContext(),
                "file" => $exception->getFile(),
                "line" => $exception->getLine(),
            ];
        }

        return new JsonResponse($data, $exception->getCode() ?: 409);
    }

    private function handleEmailDeliveryException(
        EmailDeliveryException $exception,
    ): JsonResponse {
        $this->logger->error("Email delivery exception occurred", [
            "message" => $exception->getMessage(),
            "context" => $exception->getContext(),
            "trace" => $exception->getTraceAsString(),
        ]);

        $data = [
            "success" => false,
            "error" => [
                "type" => $exception->getErrorType(),
                "message" => $this->translator->trans(
                    "Email delivery failed. Please try again later or contact support.",
                ),
            ],
        ];

        if ($this->debug) {
            $data["debug"] = [
                "original_message" => $exception->getMessage(),
                "context" => $exception->getContext(),
                "file" => $exception->getFile(),
                "line" => $exception->getLine(),
            ];
        }

        return new JsonResponse($data, $exception->getCode() ?: 503);
    }

    private function handleRateLimitExceededException(
        RateLimitExceededException $exception,
    ): JsonResponse {
        $this->logger->warning("Rate limit exceeded exception occurred", [
            "message" => $exception->getMessage(),
            "context" => $exception->getContext(),
        ]);

        $data = [
            "success" => false,
            "error" => [
                "type" => $exception->getErrorType(),
                "message" => $exception->getMessage(),
            ],
        ];

        // Add retry-after header if available
        $context = $exception->getContext();
        $retryAfter = $context["retry_after"] ?? null;

        if ($this->debug) {
            $data["debug"] = [
                "context" => $exception->getContext(),
                "file" => $exception->getFile(),
                "line" => $exception->getLine(),
            ];
        }

        $headers = [];
        if ($retryAfter) {
            $headers["Retry-After"] = (string) $retryAfter;
        }

        return new JsonResponse($data, $exception->getCode() ?: 429, $headers);
    }

    private function handleInvalidResetTokenException(
        InvalidResetTokenException $exception,
    ): JsonResponse {
        $this->logger->warning("Invalid reset token exception occurred", [
            "message" => $exception->getMessage(),
            "context" => $exception->getContext(),
        ]);

        $data = [
            "success" => false,
            "error" => [
                "type" => $exception->getErrorType(),
                "message" => $exception->getMessage(),
            ],
        ];

        if ($this->debug) {
            $data["debug"] = [
                "context" => $exception->getContext(),
                "file" => $exception->getFile(),
                "line" => $exception->getLine(),
            ];
        }

        return new JsonResponse($data, $exception->getCode() ?: 400);
    }

    private function handleExpiredResetTokenException(
        ExpiredResetTokenException $exception,
    ): JsonResponse {
        $this->logger->info("Expired reset token exception occurred", [
            "message" => $exception->getMessage(),
            "context" => $exception->getContext(),
        ]);

        $data = [
            "success" => false,
            "error" => [
                "type" => $exception->getErrorType(),
                "message" => $exception->getMessage(),
                "action_required" => "request_new_token",
            ],
        ];

        if ($this->debug) {
            $data["debug"] = [
                "context" => $exception->getContext(),
                "file" => $exception->getFile(),
                "line" => $exception->getLine(),
            ];
        }

        return new JsonResponse($data, $exception->getCode() ?: 410);
    }

    private function handleResetTokenNotFoundException(
        ResetTokenNotFoundException $exception,
    ): JsonResponse {
        $this->logger->info("Reset token not found exception occurred", [
            "message" => $exception->getMessage(),
            "context" => $exception->getContext(),
        ]);

        $data = [
            "success" => false,
            "error" => [
                "type" => $exception->getErrorType(),
                "message" => $exception->getMessage(),
            ],
        ];

        if ($this->debug) {
            $data["debug"] = [
                "context" => $exception->getContext(),
                "file" => $exception->getFile(),
                "line" => $exception->getLine(),
            ];
        }

        return new JsonResponse($data, $exception->getCode() ?: 404);
    }

    private function handleResetPasswordValidationException(
        ResetPasswordValidationException $exception,
    ): JsonResponse {
        $this->logger->warning("Reset password validation exception occurred", [
            "message" => $exception->getMessage(),
            "errors" => $exception->getErrors(),
            "context" => $exception->getContext(),
        ]);

        $data = [
            "success" => false,
            "error" => [
                "type" => $exception->getErrorType(),
                "message" => $exception->getMessage(),
                "errors" => $exception->getErrors(),
            ],
        ];

        if ($this->debug) {
            $data["debug"] = [
                "context" => $exception->getContext(),
                "file" => $exception->getFile(),
                "line" => $exception->getLine(),
            ];
        }

        return new JsonResponse($data, $exception->getCode() ?: 400);
    }
}

