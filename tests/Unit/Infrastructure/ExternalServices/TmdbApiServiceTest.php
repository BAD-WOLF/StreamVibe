<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\ExternalServices;

use App\Domain\Exception\BusinessLogicException;
use App\Domain\Exception\ExternalServiceException;
use App\Infrastructure\ExternalServices\TmdbApiService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Throwable;

/**
 * TmdbApiService (adaptador da MovieDatabaseGatewayInterface) com um
 * HttpClient falso: a URL e a query que vão para o TMDB e a tradução de
 * cada falha do TMDB para a exceção de domínio (e daí para o status da
 * nossa API).
 *
 * NOTA (out/2026): criado junto com a tradução de erros no adaptador. Não
 * havia teste nenhum desses caminhos — e era por eles que os erros do
 * TMDB chegavam ao cliente como 500. Este teste não usa rede.
 */
final class TmdbApiServiceTest extends TestCase
{
    public function testSendsTheTokenAndTheLanguage(): void
    {
        $response = new MockResponse('{"id": 550, "title": "Fight Club"}');

        $data = $this->service($response)->getMovieDetails(550, "pt-BR");

        self::assertSame(["id" => 550, "title" => "Fight Club"], $data);
        self::assertSame("GET", $response->getRequestMethod());
        self::assertSame("https://api.themoviedb.org/3/movie/550?language=pt-BR", $response->getRequestUrl());
        self::assertContains("Authorization: Bearer test-token", $response->getRequestOptions()["headers"]);
    }

    public function testOptionalParametersOnlyGoWhenGiven(): void
    {
        $response = new MockResponse('{"page": 1, "results": []}');

        $this->service($response)->searchMovies("batman");

        self::assertSame(
            "https://api.themoviedb.org/3/search/movie?query=batman&page=1&include_adult=false",
            $response->getRequestUrl(),
        );
    }

    /**
     * region, year e primary_release_year eram aceitos pela API e nunca
     * chegavam ao TMDB.
     */
    public function testSearchSendsRegionAndYears(): void
    {
        $response = new MockResponse('{"page": 2, "results": []}');

        $this->service($response)->searchMovies("batman", 2, true, "pt-BR", "BR", 2008, 2008);

        self::assertSame(
            "https://api.themoviedb.org/3/search/movie?query=batman&page=2&include_adult=true&language=pt-BR&region=BR&year=2008&primary_release_year=2008",
            $response->getRequestUrl(),
        );
    }

    public function testMissingMovieIsA404(): void
    {
        $service = $this->service(new MockResponse('{"status_code": 34}', ["http_code" => 404]));

        try {
            $service->getMovieDetails(999999999);
            self::fail("A 404 from TMDB should become a domain exception");
        } catch (BusinessLogicException $exception) {
            self::assertSame(404, $exception->getCode());
            self::assertSame('Movie with identifier "999999999" not found', $exception->getMessage());
        }
    }

    public function testMissingImageIsA404(): void
    {
        $service = $this->service(new MockResponse("", ["http_code" => 404]));

        $this->expectException(BusinessLogicException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('Image with identifier "/abc.jpg" not found');

        $service->getImage("w500", "/abc.jpg");
    }

    public function testImageBytesComeBackAsTheyAre(): void
    {
        $response = new MockResponse("\x89PNG\r\n\x1a\nbytes");

        self::assertSame("\x89PNG\r\n\x1a\nbytes", $this->service($response)->getImage("w92", "/abc.png"));
        self::assertSame("https://image.tmdb.org/t/p/w92/abc.png", $response->getRequestUrl());
    }

    /**
     * @return iterable<string, array{\Symfony\Component\HttpClient\Response\MockResponse, int, string}>
     */
    public static function failureProvider(): iterable
    {
        yield "token rejected (401)" => [new MockResponse("{}", ["http_code" => 401]), 502, "server_error"];
        yield "forbidden (403)" => [new MockResponse("{}", ["http_code" => 403]), 502, "server_error"];
        yield "other client error (422)" => [new MockResponse("{}", ["http_code" => 422]), 400, "client_error"];
        yield "server error (500)" => [new MockResponse("{}", ["http_code" => 500]), 502, "server_error"];
        yield "invalid JSON" => [new MockResponse("<html>not json</html>"), 502, "invalid_response"];
        yield "network error" => [new MockResponse("", ["error" => "Could not resolve host"]), 503, "connection_failed"];
    }

    #[DataProvider("failureProvider")]
    public function testFailuresBecomeExternalServiceErrors(MockResponse $response, int $status, string $errorType): void
    {
        try {
            $this->service($response)->getPopularMovies();
            self::fail("The failure should become a domain exception");
        } catch (ExternalServiceException $exception) {
            self::assertSame($status, $exception->getCode());
            self::assertSame($errorType, $exception->getContext()["error_type"]);
        } catch (Throwable $exception) {
            self::fail(sprintf("Expected ExternalServiceException, got %s", $exception::class));
        }
    }

    public function testRateLimitIsA503WithRetryAfter(): void
    {
        $service = $this->service(new MockResponse("{}", [
            "http_code" => 429,
            "response_headers" => ["Retry-After: 7"],
        ]));

        try {
            $service->getPopularMovies();
            self::fail("A 429 from TMDB should become a domain exception");
        } catch (ExternalServiceException $exception) {
            self::assertSame(503, $exception->getCode());
            self::assertSame("rate_limited", $exception->getContext()["error_type"]);
            self::assertSame(7, $exception->getContext()["retry_after"]);
        }
    }

    private function service(MockResponse $response): TmdbApiService
    {
        return new TmdbApiService(new MockHttpClient($response), new NullLogger(), "test-token");
    }
}
