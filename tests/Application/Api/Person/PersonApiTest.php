<?php

declare(strict_types=1);

namespace App\Tests\Application\Api\Person;

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
 * API tests for the person endpoints:
 *
 * - GET /{locale}/api/person/{personId}/details
 * - GET /{locale}/api/person/{personId}/movie/credits
 * - GET /{locale}/api/person/{personId}/tv/credits    (novo)
 * - GET /{locale}/api/person/{personId}/images        (novo)
 * - GET /{locale}/api/person/{personId}/external/ids  (novo)
 *
 * Os casos de sucesso vão ao TMDB de verdade, com Brad Pitt (287).
 *
 * NOTA (out/2026): nenhum endpoint de pessoa tinha teste. As rotas
 * antigas (/person/{id} e /person/{id}/movie-credits) mudaram de
 * propósito para /details e /movie/credits — testOldRoutesAreGone()
 * registra isso.
 */
final class PersonApiTest extends WebTestCase
{
    use DatabaseTestTrait;
    use ApiAuthenticationTrait;
    use ApiRequestTrait;

    private const int BRAD_PITT = 287;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();
        $this->entityManager = $container->get("doctrine")->getManager();

        $this->setupTestDatabase();

        $user = UserTestBuilder::create()
            ->withEmail("people@example.com")
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

    public function testDetails(): void
    {
        $this->client->request("GET", "/pt_BR/api/person/" . self::BRAD_PITT . "/details");

        $this->assertResponseIsSuccessful();
        $data = $this->responseData($this->client)["data"];
        $this->assertSame(self::BRAD_PITT, $data["id"]);
        $this->assertSame("Brad Pitt", $data["name"]);
        $this->assertSame("nm0000093", $data["imdb_id"]);
        $this->assertSame(1963, $data["birth_year"]);
        $this->assertTrue($data["is_alive"]);
        $this->assertStringStartsWith("https://image.tmdb.org/t/p/", (string) $data["profile_url"]);
    }

    public function testMovieCredits(): void
    {
        $this->client->request("GET", "/pt_BR/api/person/" . self::BRAD_PITT . "/movie/credits");

        $this->assertResponseIsSuccessful();
        $data = $this->responseData($this->client)["data"];
        $this->assertSame(["person_id", "cast", "crew"], array_keys($data));
        $this->assertSame(self::BRAD_PITT, $data["person_id"]);
        $this->assertContains(550, array_column($data["cast"], "id"), "Fight Club should be in the cast credits");
    }

    public function testTvCredits(): void
    {
        $this->client->request("GET", "/pt_BR/api/person/" . self::BRAD_PITT . "/tv/credits");

        $this->assertResponseIsSuccessful();
        $data = $this->responseData($this->client)["data"];
        $this->assertSame(["person_id", "cast", "crew"], array_keys($data));
        $this->assertNotEmpty($data["cast"]);
        $this->assertSame(
            ["id", "name", "original_name", "character", "credit_id", "episode_count", "first_air_date",
                "poster_path", "backdrop_path", "vote_average", "vote_count", "popularity", "adult", "genre_ids",
                "origin_country", "original_language", "overview", "poster_url", "backdrop_url", "first_air_year",
                "rating_percentage"],
            array_keys($data["cast"][0]),
        );
    }

    public function testImages(): void
    {
        $this->client->request("GET", "/pt_BR/api/person/" . self::BRAD_PITT . "/images");

        $this->assertResponseIsSuccessful();
        $data = $this->responseData($this->client)["data"];
        $this->assertSame(["person_id", "profiles"], array_keys($data));
        $this->assertNotEmpty($data["profiles"]);
        $this->assertStringStartsWith("https://image.tmdb.org/t/p/h632/", $data["profiles"][0]["url_h632"]);
    }

    public function testExternalIds(): void
    {
        $this->client->request("GET", "/pt_BR/api/person/" . self::BRAD_PITT . "/external/ids");

        $this->assertResponseIsSuccessful();
        $data = $this->responseData($this->client)["data"];
        $this->assertSame(self::BRAD_PITT, $data["person_id"]);
        $this->assertSame("nm0000093", $data["imdb_id"]);
    }

    public function testUnknownPersonIsA404(): void
    {
        $this->client->request("GET", "/pt_BR/api/person/999999999/details");

        $this->assertApiError($this->client, Response::HTTP_NOT_FOUND, "business_logic_error");
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function invalidPersonIdProvider(): iterable
    {
        yield "zero" => ["0", Response::HTTP_BAD_REQUEST];
        yield "above the TMDB range" => ["9999999999", Response::HTTP_BAD_REQUEST];
        yield "does not fit in an int" => ["99999999999999999999", Response::HTTP_NOT_FOUND];
    }

    #[DataProvider("invalidPersonIdProvider")]
    public function testInvalidPersonIdNeverReachesTmdb(string $personId, int $expectedStatus): void
    {
        $this->client->request("GET", "/pt_BR/api/person/" . $personId . "/details");

        $this->assertResponseStatusCodeSame($expectedStatus);
        $this->assertFalse($this->responseData($this->client)["success"]);
    }

    public function testOldRoutesAreGone(): void
    {
        $this->client->request("GET", "/pt_BR/api/person/" . self::BRAD_PITT);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $this->client->request("GET", "/pt_BR/api/person/" . self::BRAD_PITT . "/movie-credits");
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
