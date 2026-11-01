<?php

declare(strict_types=1);

namespace App\Tests\Application\Api\Image;

use App\Application\Image\Shared\ImageFormat;
use App\Application\Image\Shared\ImageSize;
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
 * API tests for the image endpoints:
 *
 * - GET /{locale}/api/images/sizes
 * - GET /{locale}/api/images/info/{endpoint}?size=
 * - GET /{locale}/api/image/{size}/{endpoint}?format=&quality=&cache=
 * - GET /{locale}/api/image/{endpoint}  (tamanho original)
 *
 * sizes, info, format=url e os erros de entrada não chamam o TMDB. Os
 * downloads de verdade usam o pôster do Fight Club, lido de
 * GET /movies/550 (o caminho do arquivo pode mudar no TMDB).
 *
 * NOTA (out/2026): nenhuma rota de imagem tinha teste. Passaram, entre
 * outros: erros de validação de imagem respondendo 500
 * (DomainException::withContext() clonava a exceção, e
 * multipleValidationErrors() recebia uma lista), w45 e w300 anunciados
 * em /images/sizes e recusados pela rota (404), quality sem validação e
 * os exemplos de uso sem o locale.
 */
final class ImageApiTest extends WebTestCase
{
    use DatabaseTestTrait;
    use ApiAuthenticationTrait;
    use ApiRequestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();
        $this->entityManager = $container->get("doctrine")->getManager();

        $this->setupTestDatabase();

        $user = UserTestBuilder::create()
            ->withEmail("images@example.com")
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
    // GET /images/sizes
    // ------------------------------------------------------------------

    public function testSizesListsTheCatalog(): void
    {
        $this->client->request("GET", "/pt_BR/api/images/sizes");

        $this->assertResponseIsSuccessful();
        $data = $this->responseData($this->client)["data"];
        $this->assertSame(
            ["supported_sizes", "supported_formats", "cache_enabled", "quality_options", "usage_examples"],
            array_keys($data),
        );
        $this->assertSame(ImageSize::BY_TYPE, $data["supported_sizes"]);
        $this->assertSame(ImageFormat::ALL, $data["supported_formats"]);
        $this->assertSame(["min" => 1, "max" => 100], $data["quality_options"]);
    }

    /**
     * Os exemplos saíam sem o prefixo de locale (inutilizáveis).
     */
    public function testUsageExamplesUseTheLocaleOfTheRequest(): void
    {
        $this->client->request("GET", "/es/api/images/sizes");

        $this->assertResponseIsSuccessful();
        $examples = $this->responseData($this->client)["data"]["usage_examples"];
        $this->assertSame("/es/api/image/w500/poster.jpg", $examples["poster_w500"]);
        $this->assertSame("/es/api/images/info/poster.jpg?size=w500", $examples["info_w500"]);
    }

    // ------------------------------------------------------------------
    // GET /images/info/{endpoint}
    // ------------------------------------------------------------------

    public function testInfoBuildsTheCdnUrl(): void
    {
        $this->client->request("GET", "/pt_BR/api/images/info/abc.jpg?size=w500");

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            [
                "endpoint" => "/abc.jpg",
                "size" => "w500",
                "url" => "https://image.tmdb.org/t/p/w500/abc.jpg",
                "file_extension" => "jpg",
            ],
            $this->responseData($this->client)["data"],
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidInfoProvider(): iterable
    {
        yield "not an image extension" => ["/pt_BR/api/images/info/abc.txt"];
        yield "unknown size" => ["/pt_BR/api/images/info/abc.jpg?size=w999"];
    }

    #[DataProvider("invalidInfoProvider")]
    public function testInfoRejectsInvalidInput(string $uri): void
    {
        $this->client->request("GET", $uri);

        $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, "user_validation_error");
    }

    // ------------------------------------------------------------------
    // GET /image/{size}/{endpoint}
    // ------------------------------------------------------------------

    public function testUrlFormatRedirectsToTheCdn(): void
    {
        $this->client->request("GET", "/pt_BR/api/image/w92/abc.jpg?format=url");

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $this->assertResponseHeaderSame("location", "https://image.tmdb.org/t/p/w92/abc.jpg");
    }

    /**
     * w45 e w300 estavam em /images/sizes e a rota respondia 404.
     *
     * @return iterable<string, array{string}>
     */
    public static function everySizeProvider(): iterable
    {
        foreach (ImageSize::ALL as $size) {
            yield $size => [$size];
        }
    }

    #[DataProvider("everySizeProvider")]
    public function testEveryAdvertisedSizeIsAccepted(string $size): void
    {
        $this->client->request("GET", "/pt_BR/api/image/" . $size . "/abc.jpg?format=url");

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    public function testOriginalImageRouteUsesTheOriginalSize(): void
    {
        $this->client->request("GET", "/pt_BR/api/image/abc.jpg?format=url");

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $this->assertResponseHeaderSame("location", "https://image.tmdb.org/t/p/original/abc.jpg");
    }

    public function testUnknownSizeDoesNotMatchTheRoute(): void
    {
        $this->client->request("GET", "/pt_BR/api/image/w999/abc.jpg");

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * Antes estes erros respondiam 500 (withContext() e
     * multipleValidationErrors() — ver NOTA da classe) ou passavam direto
     * para o GD (quality=500).
     *
     * @return iterable<string, array{string}>
     */
    public static function invalidImageRequestProvider(): iterable
    {
        yield "quality zero" => ["/pt_BR/api/image/w92/abc.jpg?quality=0"];
        yield "quality above 100" => ["/pt_BR/api/image/w92/abc.jpg?quality=500"];
        yield "unknown format" => ["/pt_BR/api/image/w92/abc.jpg?format=xml"];
        yield "not an image extension" => ["/pt_BR/api/image/w92/abc.exe"];
    }

    #[DataProvider("invalidImageRequestProvider")]
    public function testInvalidImageRequestIsA400(string $uri): void
    {
        $this->client->request("GET", $uri);

        $this->assertApiError($this->client, Response::HTTP_BAD_REQUEST, "user_validation_error");
    }

    public function testQualityThatIsNotANumberIsA400(): void
    {
        $this->client->request("GET", "/pt_BR/api/image/w92/abc.jpg?quality=high");

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testBase64ReturnsADataUri(): void
    {
        $this->client->request("GET", "/pt_BR/api/image/w92/" . $this->posterFile());

        $this->assertResponseIsSuccessful();
        $data = $this->responseData($this->client)["data"];
        $this->assertSame(ImageFormat::BASE64, $data["format"]);
        $this->assertSame("image/jpeg", $data["mime_type"]);
        $this->assertStringStartsWith("data:image/jpeg;base64,", (string) $data["base64"]);
        $this->assertGreaterThan(0, $data["content_length"]);
    }

    public function testBinaryReturnsTheImageItself(): void
    {
        $this->client->request("GET", "/pt_BR/api/image/w92/" . $this->posterFile() . "?format=binary");

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame("content-type", "image/jpeg");
        $this->assertResponseHeaderSame("x-content-type-options", "nosniff");
        $this->assertStringStartsWith("\xFF\xD8", (string) $this->client->getResponse()->getContent());
    }

    public function testImageMissingOnTmdbIsA404(): void
    {
        $this->client->request("GET", "/pt_BR/api/image/w92/doesnotexist0123456789.jpg");

        $this->assertApiError($this->client, Response::HTTP_NOT_FOUND, "business_logic_error");
    }

    /**
     * Nome do arquivo do pôster do Fight Club, sem a barra inicial.
     */
    private function posterFile(): string
    {
        $this->client->request("GET", "/pt_BR/api/movies/550");
        $this->assertResponseIsSuccessful();

        $posterPath = (string) $this->responseData($this->client)["data"]["poster_path"];
        $this->assertNotSame("", $posterPath);

        return ltrim($posterPath, "/");
    }
}
