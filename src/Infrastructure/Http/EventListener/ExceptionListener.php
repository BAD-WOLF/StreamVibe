<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\EventListener;

use App\Domain\Exception\BusinessLogicException;
use App\Domain\Exception\DomainException;
use App\Domain\Exception\EmailDeliveryException;
use App\Domain\Exception\ExpiredResetTokenException;
use App\Domain\Exception\ExternalServiceException;
use App\Domain\Exception\RateLimitExceededException;
use App\Domain\Exception\UserNotVerifiedException;
use App\Domain\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * Transforma as exceções das rotas da API no corpo de erro da API:
 *
 *     {
 *       "success": false,
 *       "error": {
 *         "type": "user_validation_error",
 *         "message": "Multiple validation errors occurred",
 *         "errors": ["Email: Please provide a valid email address"]
 *       }
 *     }
 *
 * - exceções de domínio (App\Domain\Exception): status pelo código da
 *   exceção (cada uma tem o seu: 400, 403, 404, 409, 410, 429, 502...),
 *   `type` pelo getErrorType() e a mensagem da própria exceção — exceto
 *   falha de email e de serviço externo, que respondem uma mensagem
 *   genérica (a original vai para o log e para o `debug`);
 * - `errors` só nas de validação; `action_required` em conta não
 *   verificada (email_verification) e token de troca de senha vencido
 *   (request_new_token);
 * - Retry-After quando a exceção sabe quanto esperar (rate limit nosso
 *   ou do TMDB);
 * - exceções HTTP do Symfony (404 de rota, 405, 400 de corpo/parâmetro
 *   malformado...): o status e os headers delas, type "http_error";
 * - qualquer outra: 500 "internal_error", com mensagem genérica;
 * - com kernel.debug, o bloco `debug` (contexto, arquivo, linha, trace).
 *
 * Log: 5xx como error (com trace) — ou critical, se a exceção não é
 * conhecida —, 4xx como warning.
 *
 * NOTA (out/2026): reescrito. Eram 16 métodos handle*() quase iguais (um
 * por classe de exceção) num if/elseif; o formato do corpo continua o
 * mesmo. O que mudou:
 * - PermissionDeniedException (403) e InvalidVerificationLinkException
 *   (400) caíam no handler genérico de DomainException, que logava como
 *   error: um 4xx esperado agora é warning;
 * - exceção HTTP pela interface (HttpExceptionInterface), e com os
 *   headers dela — antes o Allow do 405 se perdia;
 * - Retry-After também no 503 de rate limit do TMDB
 *   (ExternalServiceException::rateLimited());
 * - código fora da faixa HTTP (ex.: 0) usa o status padrão do tipo, em
 *   vez de virar um JsonResponse com status inválido.
 */
final readonly class ExceptionListener
{
    /**
     * Mensagens que substituem a da exceção (detalhe interno que não vai
     * para o cliente).
     */
    private const string EMAIL_DELIVERY_MESSAGE = "Email delivery failed. Please try again later or contact support.";

    private const string EXTERNAL_SERVICE_MESSAGE = "External service temporarily unavailable. Please try again later.";

    private const string INTERNAL_ERROR_MESSAGE = "An internal error occurred. Please try again later.";

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
        if (!$this->isApiRequest($event->getRequest())) {
            return;
        }

        $exception = $event->getThrowable();

        $response = match (true) {
            $exception instanceof DomainException => $this->domainErrorResponse($exception),
            $exception instanceof HttpExceptionInterface => $this->httpErrorResponse($exception),
            default => $this->internalErrorResponse($exception),
        };

        $event->setResponse($response);
    }

    /**
     * Rotas da API: as do API Platform (atributo _api_resource_class), as
     * que têm /api no caminho (com ou sem o prefixo de locale) e as
     * requisições JSON.
     *
     * NOTA: str_starts_with(pathInfo, "/api") sozinho falhava para as
     * rotas com prefixo de locale ("/pt_BR/api/movies/search/..."): o
     * listener não interceptava a exceção e o API Platform respondia o
     * erro dele (application/problem+json, 500) em vez do status certo.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return bool
     */
    private function isApiRequest(Request $request): bool
    {
        $pathInfo = $request->getPathInfo();

        return str_starts_with($pathInfo, "/api")
            || str_contains($pathInfo, "/api/")
            || $request->attributes->get("_api_resource_class") !== null
            || str_contains($request->headers->get("Content-Type", ""), "application/json")
            || str_contains($request->headers->get("Accept", ""), "application/json");
    }

    /**
     * @param \App\Domain\Exception\DomainException $exception
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    private function domainErrorResponse(DomainException $exception): JsonResponse
    {
        $status = $this->statusFor($exception);

        // Detalhe de infraestrutura (SMTP, TMDB) não vai para o cliente.
        $publicMessage = match (true) {
            $exception instanceof EmailDeliveryException => $this->translator->trans(self::EMAIL_DELIVERY_MESSAGE),
            $exception instanceof ExternalServiceException => $this->translator->trans(self::EXTERNAL_SERVICE_MESSAGE),
            default => $exception->getMessage(),
        };

        $error = [
            "type" => $exception->getErrorType(),
            "message" => $publicMessage,
        ];
        $logContext = [
            "error_type" => $exception->getErrorType(),
            "context" => $exception->getContext(),
        ];

        if ($exception instanceof ValidationException) {
            $error["errors"] = $exception->getErrors();
            $logContext["errors"] = $exception->getErrors();
        }

        $actionRequired = match (true) {
            $exception instanceof UserNotVerifiedException => "email_verification",
            $exception instanceof ExpiredResetTokenException => "request_new_token",
            default => null,
        };

        if ($actionRequired !== null) {
            $error["action_required"] = $actionRequired;
        }

        $this->log($status, "Domain exception occurred", $exception, $logContext);

        $debug = [
            "context" => $exception->getContext(),
        ];

        if ($publicMessage !== $exception->getMessage()) {
            $debug["original_message"] = $exception->getMessage();
        }

        return $this->errorResponse(
            $error,
            $status,
            $this->retryAfterHeader($exception),
            $exception,
            $debug,
        );
    }

    /**
     * @param \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface&\Throwable $exception
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    private function httpErrorResponse(HttpExceptionInterface&Throwable $exception): JsonResponse
    {
        $status = $exception->getStatusCode();

        $this->log($status, "HTTP exception occurred", $exception, [
            "status_code" => $status,
        ]);

        return $this->errorResponse(
            [
                "type" => "http_error",
                "message" => $exception->getMessage() ?: "An HTTP error occurred",
            ],
            $status,
            $exception->getHeaders(),
            $exception,
            ["headers" => $exception->getHeaders()],
        );
    }

    /**
     * @param \Throwable $exception
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    private function internalErrorResponse(Throwable $exception): JsonResponse
    {
        $this->logger->critical("Unhandled exception occurred", [
            "message" => $exception->getMessage(),
            "class" => $exception::class,
            "file" => $exception->getFile(),
            "line" => $exception->getLine(),
            "trace" => $exception->getTraceAsString(),
        ]);

        return $this->errorResponse(
            [
                "type" => "internal_error",
                "message" => $this->translator->trans(self::INTERNAL_ERROR_MESSAGE),
            ],
            500,
            [],
            $exception,
            [
                "original_message" => $exception->getMessage(),
                "class" => $exception::class,
                "memory_usage" => memory_get_usage(true),
                "peak_memory" => memory_get_peak_usage(true),
            ],
        );
    }

    /**
     * Corpo de erro da API, com o bloco `debug` quando kernel.debug está
     * ligado.
     *
     * @param array<string, mixed>  $error
     * @param int                   $status
     * @param array<string, string> $headers
     * @param \Throwable            $exception
     * @param array<string, mixed>  $debug dados do `debug` além de arquivo, linha e trace
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    private function errorResponse(
        array $error,
        int $status,
        array $headers,
        Throwable $exception,
        array $debug,
    ): JsonResponse {
        $data = [
            "success" => false,
            "error" => $error,
        ];

        if ($this->debug) {
            $data["debug"] = $debug + [
                "file" => $exception->getFile(),
                "line" => $exception->getLine(),
                "trace" => explode("\n", $exception->getTraceAsString()),
            ];
        }

        return new JsonResponse($data, $status, $headers);
    }

    /**
     * Status HTTP de uma exceção de domínio: o código dela, ou o padrão do
     * tipo se o código não for um status HTTP de erro (4xx/5xx).
     *
     * @param \App\Domain\Exception\DomainException $exception
     *
     * @return int
     */
    private function statusFor(DomainException $exception): int
    {
        $code = $exception->getCode();

        if ($code >= 400 && $code <= 599) {
            return $code;
        }

        return match (true) {
            $exception instanceof ValidationException => 400,
            $exception instanceof BusinessLogicException => 422,
            $exception instanceof RateLimitExceededException => 429,
            $exception instanceof ExternalServiceException => 502,
            $exception instanceof EmailDeliveryException => 503,
            default => 500,
        };
    }

    /**
     * Retry-After (segundos) quando a exceção traz context["retry_after"]:
     * RateLimitExceededException (troca de senha pedida há pouco) e
     * ExternalServiceException::rateLimited() (limite do TMDB).
     *
     * @param \App\Domain\Exception\DomainException $exception
     *
     * @return array<string, string>
     */
    private function retryAfterHeader(DomainException $exception): array
    {
        $retryAfter = $exception->getContext()["retry_after"] ?? null;

        if (!is_int($retryAfter) || $retryAfter <= 0) {
            return [];
        }

        return ["Retry-After" => (string) $retryAfter];
    }

    /**
     * 5xx como error (com trace), 4xx como warning.
     *
     * @param int                  $status
     * @param string               $message
     * @param \Throwable           $exception
     * @param array<string, mixed> $context
     *
     * @return void
     */
    private function log(int $status, string $message, Throwable $exception, array $context): void
    {
        $isServerError = $status >= 500;

        $context = [
            "exception_class" => $exception::class,
            "message" => $exception->getMessage(),
            "status_code" => $status,
        ] + $context;

        if ($isServerError || $this->debug) {
            $context["trace"] = $exception->getTraceAsString();
        }

        $this->logger->log(
            $isServerError ? LogLevel::ERROR : LogLevel::WARNING,
            $message,
            $context,
        );
    }
}
