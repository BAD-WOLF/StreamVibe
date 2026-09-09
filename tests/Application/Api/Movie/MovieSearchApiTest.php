<?php

declare(strict_types=1);

namespace App\Tests\Application\Api\Movie;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * API tests for movie search functionality
 *
 * Tests the movie search endpoint for various scenarios including
 * successful searches, error handling, pagination, and edge cases.
 *
 * NOTA 1: assertStringContainsString é case-sensitive. A mensagem real da
 * exceção é "Required field 'query' is missing" (R maiúsculo) — o teste
 * checava "required" (minúsculo) e nunca batia mesmo com o código 400
 * correto. Corrigido para "Required".
 *
 * NOTA 2: PHPUnit 12 não lê mais a anotação @dataProvider em docblock —
 * precisa do atributo #[DataProvider(...)]. Sem isso, o método de teste
 * era chamado com 0 argumentos (ArgumentCountError).
 */
final class MovieSearchApiTest extends WebTestCase
{
    private const API_ENDPOINT = "/pt_BR/api/movies/search";

    public function testSuccessfulMovieSearch(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $client->request("GET", self::API_ENDPOINT . "/batman/1");

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame("content-type", "application/json");

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertValidSuccessResponse($responseData);
        $this->assertEquals(1, $responseData["data"]["current_page"]);
    }

    public function testMovieSearchWithEmptyQuery(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $client->request("GET", self::API_ENDPOINT . "//1");

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($responseData["success"]);
        // NOTA: a mensagem vem aninhada em error.message, formato do
        // ExceptionListener (success/error{type,message,errors}) — não no
        // nível raiz.
        $this->assertArrayHasKey("error", $responseData);
        $this->assertArrayHasKey("message", $responseData["error"]);
        $this->assertStringContainsString("Required", $responseData["error"]["message"]);
    }

    public function testMovieSearchWithSpecificPage(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $client->request("GET", self::API_ENDPOINT . "/star wars/2");

        // Assert
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertValidSuccessResponse($responseData);
        $this->assertEquals(2, $responseData["data"]["current_page"]);
    }

    public function testMovieSearchWithSpecialCharacters(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $client->request(
            "GET",
            self::API_ENDPOINT . "/spider-man: homecoming/1",
        );

        // Assert
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertValidSuccessResponse($responseData);
    }

    public function testMovieSearchWithUnicodeCharacters(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $client->request("GET", self::API_ENDPOINT . "/amélie/1");

        // Assert
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertValidSuccessResponse($responseData);
    }

    public function testMovieSearchWithNonExistentMovie(): void
    {
        // Arrange
        $client = static::createClient();
        $randomQuery = "xyznonexistentmovie123456789";

        // Act
        $client->request("GET", self::API_ENDPOINT . "/{$randomQuery}/1");

        // Assert
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertValidSuccessResponse($responseData);

        $data = $responseData["data"];
        $this->assertEquals(0, $data["total_results"]);
        $this->assertEmpty($data["movies"]);
    }

    public function testMovieSearchResponseContainsValidMovieData(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $client->request("GET", self::API_ENDPOINT . "/avengers/1");

        // Assert
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $movies = $responseData["data"]["movies"];

        // If movies are found, verify their structure
        if (!empty($movies)) {
            $firstMovie = $movies[0];

            $this->assertArrayHasKey("id", $firstMovie);
            $this->assertArrayHasKey("title", $firstMovie);
            $this->assertIsInt($firstMovie["id"]);
            $this->assertIsString($firstMovie["title"]);

            // Optional fields validation
            if (isset($firstMovie["overview"])) {
                $this->assertIsString($firstMovie["overview"]);
            }
            if (isset($firstMovie["release_date"])) {
                $this->assertIsString($firstMovie["release_date"]);
            }
            if (isset($firstMovie["vote_average"])) {
                $this->assertIsNumeric($firstMovie["vote_average"]);
            }
        }
    }

    public function testMovieSearchPaginationMetadataConsistency(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $client->request("GET", self::API_ENDPOINT . "/marvel/1");

        // Assert
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $data = $responseData["data"];

        // Verify pagination metadata consistency
        $this->assertGreaterThanOrEqual(0, $data["total_results"]);
        $this->assertGreaterThanOrEqual(0, $data["total_pages"]);
        $this->assertGreaterThanOrEqual(1, $data["current_page"]);

        // Logical relationships validation
        if ($data["total_results"] > 0) {
            $this->assertGreaterThan(0, $data["total_pages"]);
        }
    }

    public function testMovieSearchHandlesLongQueries(): void
    {
        // Arrange
        $client = static::createClient();
        $longQuery = str_repeat("batman", 20); // Long but reasonable query

        // Act
        $client->request(
            "GET",
            self::API_ENDPOINT . "/" . urlencode($longQuery) . "/1",
        );

        // Assert
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertValidSuccessResponse($responseData);
    }

    public function testMovieSearchWithMultipleWordsInQuery(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $client->request(
            "GET",
            self::API_ENDPOINT . "/" . urlencode("iron man") . "/1",
        );

        // Assert
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertValidSuccessResponse($responseData);
    }

    #[DataProvider("invalidPageProvider")]
    public function testMovieSearchHandlesInvalidPages(
        string $page,
        string $description,
    ): void {
        // Arrange
        $client = static::createClient();

        // Act
        $client->request("GET", self::API_ENDPOINT . "/batman/{$page}");

        // Assert - Should either succeed with page 1 or return appropriate error
        // NOTA: a rota tem requirement 'page' => '\d+'. Valores negativos
        // ou não-numéricos nunca batem com o padrão da rota, então o
        // Symfony devolve 404 (rota não encontrada) antes mesmo de chegar
        // no controller — não 400. Ambos contam como "erro apropriado".
        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() ||
            $response->getStatusCode() === Response::HTTP_BAD_REQUEST ||
            $response->getStatusCode() === Response::HTTP_NOT_FOUND,
            "Failed for case: {$description}",
        );

        if ($response->isSuccessful()) {
            $responseData = json_decode($response->getContent(), true);
            $this->assertValidSuccessResponse($responseData);
        }
    }

    public function testMovieSearchAcceptsCorrectContentType(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $client->request(
            "GET",
            self::API_ENDPOINT . "/test/1",
            [],
            [],
            ["HTTP_ACCEPT" => "application/json"],
        );

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame("content-type", "application/json");
    }

    public function testMovieSearchPerformance(): void
    {
        // Arrange
        $client = static::createClient();

        // Act - Measure search response time
        $start = microtime(true);
        $client->request("GET", self::API_ENDPOINT . "/performance test/1");
        $end = microtime(true);
        $duration = $end - $start;

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertLessThan(
            5.0,
            $duration,
            "Movie search should complete within 5 seconds",
        );

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertValidSuccessResponse($responseData);
    }

    /**
     * Data provider for invalid page test cases
     */
    public static function invalidPageProvider(): array
    {
        return [
            ["0", "zero page"],
            ["-1", "negative page"],
            ["invalid", "non-numeric page"],
            ["999999", "extremely high page number"],
        ];
    }

    /**
     * Assert that response has valid success structure
     */
    private function assertValidSuccessResponse(array $responseData): void
    {
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey("success", $responseData);
        $this->assertArrayHasKey("data", $responseData);
        $this->assertTrue($responseData["success"]);

        $data = $responseData["data"];
        $this->assertArrayHasKey("movies", $data);
        $this->assertArrayHasKey("total_results", $data);
        $this->assertArrayHasKey("total_pages", $data);
        $this->assertArrayHasKey("current_page", $data);

        $this->assertIsArray($data["movies"]);
        $this->assertIsInt($data["total_results"]);
        $this->assertIsInt($data["total_pages"]);
        $this->assertIsInt($data["current_page"]);
    }
}

