<?php

declare(strict_types=1);

namespace App\Tests\Application\Api\Movie;

use App\Domain\User\Entity\User;
use App\Infrastructure\Persistence\Repository\UserRepository;
use App\Tests\Helper\ApiAuthenticationTrait;
use App\Tests\Helper\ApiRequestTrait;
use App\Tests\Helper\DatabaseTestTrait;
use App\Tests\Helper\UserTestBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * API tests for the movie endpoints other than search (popular,
 * top-rated, now-playing, trending, details, credits, images, videos,
 * similar, recommendations).
 *
 * Como o MovieSearchApiTest, os casos de sucesso vão ao TMDB de verdade
 * (TMDB_TOKEN do cofre de teste), com Fight Club (550) como filme
 * conhecido. Os casos de erro de entrada (id, página, idioma, janela de
 * tendência) param antes do TMDB — não dependem de rede.
 *
 * NOTA (out/2026): até aqui só a busca tinha teste de API. Por isso
 * passaram despercebidos, entre outros:
 * - id com mais de 18 dígitos casava com a rota ('\d+') e o controller
 *   (int $movieId) recebia um número que não cabe em int — TypeError, 500;
 * - erro do TMDB (filme inexistente, token recusado, rate limit) virava
 *   500 nos UseCases;
 * - /movies/trending/{timeWindow} ignorava o {timeWindow} (sempre "day");
 * - `language` era aceito e nunca chegava ao TMDB;
 * - o JSON real (toArray()) era diferente do que o Swagger documentava.
 */
final class MovieApiTest extends WebTestCase
{
    use DatabaseTestTrait;
    use ApiAuthenticationTrait;
    use ApiRequestTrait;

    private const int FIGHT_CLUB = 550;

    /**
     * Campos de cada filme em `data.movies` (MovieListItem).
     */
    private const array MOVIE_LIST_ITEM_KEYS = [
        "id", "title", "original_title", "overview", "release_date", "poster_path", "backdrop_path",
        "vote_average", "vote_count", "popularity", "genre_ids", "adult", "video", "original_language",
        "poster_url", "backdrop_url", "thumbnail_url", "rating_percentage", "release_year", "formatted_overview",
    ];

    private const array PAGINATION_KEYS = [
        "current_page", "total_pages", "total_results", "has_next", "has_previous", "next_page", "previous_page",
    ];

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();
        $this->entityManager = $container->get("doctrine")->getManager();

        $this->setupTestDatabase();

        $user = UserTestBuilder::create()
            ->withEmail("movies@example.com")
            ->withPassword("hashed_password_123")
            ->verified()
            ->build();

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $userRepository->save($user);

        $this->authenticateClient($this->client, $user);
    }

    protected function tearDown(): void
    {
        $this->teardownTestDatabase();
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Listas
    // ------------------------------------------------------------------

    /**
     * @return iterable<string, array{string}>
     */
    public static function listProvider(): iterable
    {
        yield "popular" => ["/pt_BR/api/movies/popular"];
        yield "top rated" => ["/pt_BR/api/movies/top-rated"];
        yield "now playing" => ["/pt_BR/api/movies/now-playing"];
    }

    #[DataProvider("listProvider")]
    public function testListReturnsMoviesAndPagination(string $uri): void
    {
        $this->client->request("GET", $uri);

        $this->assertResponseIsSuccessful();
        $data = $this->responseData($this->client);
        $this->assertSame(["success", "data", "message"], array_keys($data));
        $this->assertSame(array_merge(["movies"], self::PAGINATION_KEYS), array_keys($data["data"]));
        $this->assertNotEmpty($data["data"]["movies"]);
        $this->assertSame(self::MOVIE_LIST_ITEM_KEYS, array_keys($data["data"]["movies"][0]));
        $this->assertSame(1, $data["data"]["current_page"]);
        $this->assertFalse($data["data"]["has_previous"]);
        $this->assertNull($data["data"]["previous_page"]);
    }

    public function testListSecondPage(): void
    {
        $this->client->request("GET", "/pt_BR/api/movies/popular?page=2");

        $this->assertResponseIsSuccessful();
        $data = $this->responseData($this->client)["data"];
        $this->assertSame(2, $data["current_page"]);
        $this->assertTrue($data["has_previous"]);
        $this->assertSame(1, $data["previous_page"]);
    }

    /**
     * O {timeWindow} era ignorado (a resposta era sempre a de "day").
     */
    public function testTrendingUsesTheTimeWindow(): void
    {
        foreach (["day", "week"] as $timeWindow) {
            $this->client->request("GET", "/pt_BR/api/movies/trending/" . $timeWindow);

            $this->assertResponseIsSuccessful();
            $data = $this->responseData($this->client)["data"];
            $this->assertSame($timeWindow, $data["time_window"]);
            $this->assertSame(array_merge(["time_window", "movies"], self::PAGINATION_KEYS), array_keys($data));
        }
    }

    public function testTrendingRejectsAnUnknownTimeWindow(): void
    {
        $this->client->request("GET", "/pt_BR/api/movies/trending/month");

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidPageProvider(): iterable
    {
        yield "zero" => ["0"];
        yield "above the TMDB limit" => ["501"];
        yield "not a number" => ["abc"];
    }

    /**
     * Antes a página inválida era trocada por 1 em silêncio.
     */
    #[DataProvider("invalidPageProvider")]
    public function testListRejectsAnInvalidPage(string $page): void
    {
        $this->client->request("GET", "/pt_BR/api/movies/popular?page=" . $page);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertFalse($this->responseData($this->client)["success"]);
    }

    // ------------------------------------------------------------------
    // Um filme
    // ------------------------------------------------------------------

    public function testDetailsOfAKnownMovie(): void
    {
        $this->client->request("GET", "/pt_BR/api/movies/" . self::FIGHT_CLUB);

        $this->assertResponseIsSuccessful();
        $data = $this->responseData($this->client)["data"];
        $this->assertSame(self::FIGHT_CLUB, $data["id"]);
        $this->assertSame("Fight Club", $data["original_title"]);
        $this->assertSame(1999, $data["release_year"]);
        $this->assertStringStartsWith("https://image.tmdb.org/t/p/w500/", (string) $data["poster_url"]);
        $this->assertNotEmpty($data["genres"]);
        $this->assertSame(["id", "name"], array_keys($data["genres"][0]));
        $this->assertArrayNotHasKey("message", $data);
    }

    /**
     * `language` era aceito e nunca chegava ao TMDB.
     */
    public function testDetailsInAnotherLanguage(): void
    {
        $this->client->request("GET", "/pt_BR/api/movies/" . self::FIGHT_CLUB . "?language=pt-BR");

        $this->assertResponseIsSuccessful();
        $this->assertSame("Clube da Luta", $this->responseData($this->client)["data"]["title"]);
    }

    public function testCredits(): void
    {
        $this->client->request("GET", "/pt_BR/api/movies/" . self::FIGHT_CLUB . "/credits");

        $this->assertResponseIsSuccessful();
        $data = $this->responseData($this->client)["data"];
        $this->assertSame(["movie_id", "cast", "crew"], array_keys($data));
        $this->assertSame(self::FIGHT_CLUB, $data["movie_id"]);
        $this->assertNotEmpty($data["cast"]);
        $this->assertSame(
            ["adult", "gender", "id", "known_for_department", "name", "original_name", "popularity",
                "profile_path", "cast_id", "character", "credit_id", "order"],
            array_keys($data["cast"][0]),
        );
        $this->assertContains("Director", array_column($data["crew"], "job"));
    }

    public function testImages(): void
    {
        $this->client->request("GET", "/pt_BR/api/movies/" . self::FIGHT_CLUB . "/images");

        $this->assertResponseIsSuccessful();
        $data = $this->responseData($this->client)["data"];
        $this->assertSame(["movie_id", "backdrops", "posters", "logos"], array_keys($data));
        $this->assertNotEmpty($data["posters"]);
        $this->assertStringStartsWith("https://image.tmdb.org/t/p/original/", $data["posters"][0]["url_original"]);
    }

    public function testVideos(): void
    {
        $this->client->request("GET", "/pt_BR/api/movies/" . self::FIGHT_CLUB . "/videos");

        $this->assertResponseIsSuccessful();
        $data = $this->responseData($this->client)["data"];
        $this->assertSame(["movie_id", "videos", "trailers", "teasers", "clips"], array_keys($data));

        foreach ($data["trailers"] as $trailer) {
            $this->assertSame("trailer", strtolower($trailer["type"]));
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function relatedProvider(): iterable
    {
        yield "similar" => ["similar"];
        yield "recommendations" => ["recommendations"];
    }

    #[DataProvider("relatedProvider")]
    public function testRelatedMovies(string $relation): void
    {
        $this->client->request("GET", "/pt_BR/api/movies/" . self::FIGHT_CLUB . "/" . $relation . "?page=1");

        $this->assertResponseIsSuccessful();
        $data = $this->responseData($this->client)["data"];
        $this->assertSame(array_merge(["movie_id", "movies"], self::PAGINATION_KEYS), array_keys($data));
        $this->assertSame(self::FIGHT_CLUB, $data["movie_id"]);
    }

    /**
     * Antes: o 404 do TMDB virava 500 (ou 400, como "erro do cliente").
     */
    public function testUnknownMovieIsA404(): void
    {
        $this->client->request("GET", "/pt_BR/api/movies/999999999/credits");

        $error = $this->assertApiError($this->client, Response::HTTP_NOT_FOUND, "business_logic_error");
        $this->assertSame('Movie with identifier "999999999" not found', $error["message"]);
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function invalidMovieIdProvider(): iterable
    {
        yield "zero" => ["0", Response::HTTP_BAD_REQUEST];
        yield "above the TMDB range" => ["9999999999", Response::HTTP_BAD_REQUEST];
        // Antes: casava com '\d+', não cabia em int — TypeError, 500.
        yield "does not fit in an int" => ["99999999999999999999", Response::HTTP_NOT_FOUND];
        yield "not a number" => ["abc", Response::HTTP_NOT_FOUND];
    }

    #[DataProvider("invalidMovieIdProvider")]
    public function testInvalidMovieIdNeverReachesTmdb(string $movieId, int $expectedStatus): void
    {
        $this->client->request("GET", "/pt_BR/api/movies/" . $movieId . "/credits");

        $this->assertResponseStatusCodeSame($expectedStatus);
        $this->assertFalse($this->responseData($this->client)["success"]);
    }

    public function testInvalidLanguageIsA400(): void
    {
        $this->client->request("GET", "/pt_BR/api/movies/" . self::FIGHT_CLUB . "?language=portuguese");

        $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, "user_validation_error");
    }

    /**
     * Antes: o array chegava ao Request DTO (TypeError, 500).
     */
    public function testLanguageAsAnArrayIsA400(): void
    {
        $this->client->request("GET", "/pt_BR/api/movies/" . self::FIGHT_CLUB . "?language[]=pt-BR");

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
