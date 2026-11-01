<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http\EventListener;

use App\Domain\Exception\BusinessLogicException;
use App\Domain\Exception\EmailDeliveryException;
use App\Domain\Exception\ExpiredResetTokenException;
use App\Domain\Exception\ExternalServiceException;
use App\Domain\Exception\InvalidVerificationLinkException;
use App\Domain\Exception\PermissionDeniedException;
use App\Domain\Exception\RateLimitExceededException;
use App\Domain\Exception\UserNotVerifiedException;
use App\Domain\Exception\UserValidationException;
use App\Infrastructure\Http\EventListener\ExceptionListener;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Translation\IdentityTranslator;
use Throwable;

/**
 * ExceptionListener: o corpo de erro da API para cada tipo de exceção.
 *
 * NOTA (out/2026): criado com a reescrita do listener. Até aqui o
 * formato de erro só era conferido indiretamente pelos testes de API (e
 * só para validação e 409).
 */
final class ExceptionListenerTest extends TestCase
{
    public function testValidationErrorIs400WithTheFieldErrors(): void
    {
        $response = $this->handle(UserValidationException::multipleValidationErrors([
            "email" => "Please provide a valid email address",
        ]));

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(
            [
                "success" => false,
                "error" => [
                    "type" => "user_validation_error",
                    "message" => "Multiple validation errors occurred",
                    "errors" => ["Email: Please provide a valid email address"],
                ],
            ],
            $this->body($response),
        );
    }

    public function testUnverifiedAccountAsksForTheEmailVerification(): void
    {
        $response = $this->handle(new UserNotVerifiedException("Please verify your email address."));

        self::assertSame(403, $response->getStatusCode());
        self::assertSame("email_verification", $this->body($response)["error"]["action_required"]);
    }

    public function testExpiredResetTokenAsksForANewToken(): void
    {
        $response = $this->handle(ExpiredResetTokenException::tokenExpired(str_repeat("a", 40)));

        self::assertSame(410, $response->getStatusCode());
        self::assertSame("expired_reset_token_error", $this->body($response)["error"]["type"]);
        self::assertSame("request_new_token", $this->body($response)["error"]["action_required"]);
    }

    /**
     * Antes estas duas caíam no handler genérico de DomainException
     * (logadas como error); o status e o corpo eram os mesmos.
     */
    public function testPermissionDeniedAndInvalidLinkKeepTheirStatusAndType(): void
    {
        $denied = $this->handle(PermissionDeniedException::notOwner("password reset requests", 42, 99));
        self::assertSame(403, $denied->getStatusCode());
        self::assertSame(
            ["type" => "permission_denied_error", "message" => "You can only access your own password reset requests"],
            $this->body($denied)["error"],
        );

        $invalidLink = $this->handle(InvalidVerificationLinkException::fromReason("The link to verify your email is invalid.", 42));
        self::assertSame(400, $invalidLink->getStatusCode());
        self::assertSame("invalid_verification_link_error", $this->body($invalidLink)["error"]["type"]);
    }

    public function testExternalServiceDetailsStayOutOfTheResponse(): void
    {
        $exception = ExternalServiceException::connectionFailed("TMDB", "Could not resolve host: api.themoviedb.org");

        $response = $this->handle($exception);

        self::assertSame(503, $response->getStatusCode());
        self::assertSame(
            [
                "success" => false,
                "error" => [
                    "type" => "external_service_error",
                    "message" => "External service temporarily unavailable. Please try again later.",
                ],
            ],
            $this->body($response),
        );
    }

    public function testDebugModeAddsTheOriginalMessage(): void
    {
        $exception = new EmailDeliveryException("Failed to send the email confirmation");

        $body = $this->body($this->handle($exception, debug: true));

        self::assertSame(
            "Email delivery failed. Please try again later or contact support.",
            $body["error"]["message"],
        );
        self::assertSame("Failed to send the email confirmation", $body["debug"]["original_message"]);
        self::assertArrayHasKey("trace", $body["debug"]);
    }

    public function testRateLimitSendsRetryAfter(): void
    {
        $response = $this->handle(new RateLimitExceededException(
            message: "Too many password reset requests. Please wait before trying again.",
            context: ["retry_after" => 120],
        ));

        self::assertSame(429, $response->getStatusCode());
        self::assertSame("120", $response->headers->get("Retry-After"));
    }

    public function testTmdbRateLimitSendsRetryAfter(): void
    {
        $response = $this->handle(ExternalServiceException::rateLimited("TMDB", 30));

        self::assertSame(503, $response->getStatusCode());
        self::assertSame("30", $response->headers->get("Retry-After"));
    }

    public function testHttpExceptionKeepsItsStatusAndHeaders(): void
    {
        $response = $this->handle(new MethodNotAllowedHttpException(["GET"], "Method Not Allowed"));

        self::assertSame(405, $response->getStatusCode());
        self::assertSame("GET", $response->headers->get("Allow"));
        self::assertSame(
            ["type" => "http_error", "message" => "Method Not Allowed"],
            $this->body($response)["error"],
        );
    }

    /**
     * O HttpKernel converte as exceções de requisição malformada
     * (RequestExceptionInterface: parâmetro com tipo errado, JSON
     * inválido...) em BadRequestHttpException antes de chamar o listener.
     */
    public function testMalformedRequestIsA400(): void
    {
        $response = $this->handle(new BadRequestHttpException('Input value "page" is invalid'));

        self::assertSame(400, $response->getStatusCode());
        self::assertSame("http_error", $this->body($response)["error"]["type"]);
    }

    public function testUnknownErrorIsA500WithoutItsMessage(): void
    {
        $response = $this->handle(new RuntimeException("SQLSTATE[08006] connection refused"));

        self::assertSame(500, $response->getStatusCode());
        self::assertSame(
            ["type" => "internal_error", "message" => "An internal error occurred. Please try again later."],
            $this->body($response)["error"],
        );
        self::assertArrayNotHasKey("debug", $this->body($response));
    }

    public function testCodeOutsideTheHttpRangeFallsBackToTheTypeDefault(): void
    {
        $response = $this->handle(new BusinessLogicException("Invalid operation", 0));

        self::assertSame(422, $response->getStatusCode());
    }

    public function testRequestsOutsideTheApiAreLeftAlone(): void
    {
        $event = $this->event(new RuntimeException("boom"), Request::create("/health"));

        $this->listener()->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    private function handle(Throwable $exception, bool $debug = false): Response
    {
        $event = $this->event($exception, Request::create("/pt_BR/api/movies/popular"));

        $this->listener($debug)->onKernelException($event);

        $response = $event->getResponse();
        self::assertNotNull($response);

        return $response;
    }

    private function event(Throwable $exception, Request $request): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );
    }

    private function listener(bool $debug = false): ExceptionListener
    {
        return new ExceptionListener(new NullLogger(), new IdentityTranslator(), $debug);
    }

    /**
     * @return array<string, mixed>
     */
    private function body(Response $response): array
    {
        return json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }
}
