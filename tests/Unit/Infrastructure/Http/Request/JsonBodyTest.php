<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http\Request;

use App\Domain\Exception\UserValidationException;
use App\Infrastructure\Http\Request\JsonBody;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * JsonBody: leitura tipada do corpo JSON nos controllers de
 * autenticação.
 *
 * NOTA (out/2026): criado com o JsonBody. Os casos de tipo errado
 * ({"email": 123}, {"agreeTerms": ["x"]}) respondiam 500 antes — o valor
 * ia direto para o construtor do Request DTO (TypeError com
 * strict_types) ou para o trim() do reenvio.
 */
final class JsonBodyTest extends TestCase
{
    public function testReadsStringAndBooleanFields(): void
    {
        $body = JsonBody::fromRequest($this->request('{"email": "user@example.com", "agreeTerms": true}'));

        self::assertSame("user@example.com", $body->string("email"));
        self::assertTrue($body->bool("agreeTerms"));
    }

    public function testMissingOrNullFieldsUseTheDefaults(): void
    {
        $body = JsonBody::fromRequest($this->request('{"email": null}'));

        self::assertSame("", $body->string("email"));
        self::assertSame("", $body->string("password"));
        self::assertFalse($body->bool("agreeTerms"));
        self::assertTrue($body->bool("agreeTerms", default: true));
    }

    public function testEmptyObjectIsAccepted(): void
    {
        self::assertSame("", JsonBody::fromRequest($this->request("{}"))->string("email"));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidBodyProvider(): iterable
    {
        yield "empty body" => [""];
        yield "malformed JSON" => ['{"email": "user@example.com"'];
        yield "form data" => ["email=user%40example.com&password=secret"];
        yield "JSON list" => ['["user@example.com"]'];
        yield "JSON string" => ['"user@example.com"'];
        yield "JSON number" => ["42"];
        yield "JSON null" => ["null"];
    }

    #[DataProvider("invalidBodyProvider")]
    public function testBodyThatIsNotAJsonObjectIsA400(string $content): void
    {
        try {
            JsonBody::fromRequest($this->request($content));
            self::fail("The body should be rejected");
        } catch (UserValidationException $exception) {
            self::assertSame(400, $exception->getCode());
            self::assertSame("Invalid request body", $exception->getMessage());
            self::assertSame(["The request body must be a valid JSON object"], $exception->getErrors());
            // O corpo pode ter senha: não vai para a exceção.
            self::assertSame(["field" => "request_body"], $exception->getContext());
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonStringProvider(): iterable
    {
        yield "number" => ['{"email": 123}'];
        yield "boolean" => ['{"email": true}'];
        yield "array" => ['{"email": ["user@example.com"]}'];
        yield "object" => ['{"email": {"value": "user@example.com"}}'];
    }

    #[DataProvider("nonStringProvider")]
    public function testNonStringValueIsA400WithTheFieldName(string $content): void
    {
        $body = JsonBody::fromRequest($this->request($content));

        try {
            $body->string("email");
            self::fail("A non-string email should be rejected");
        } catch (UserValidationException $exception) {
            self::assertSame(400, $exception->getCode());
            self::assertSame(["Email: This value should be of type string."], $exception->getErrors());
        }
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function booleanProvider(): iterable
    {
        yield "true" => ["true", true];
        yield "false" => ["false", false];
        yield "1" => ["1", true];
        yield "0" => ["0", false];
        yield '"true"' => ['"true"', true];
        yield '"false"' => ['"false"', false];
        yield '"yes"' => ['"yes"', true];
        yield '"off"' => ['"off"', false];
    }

    #[DataProvider("booleanProvider")]
    public function testBooleanAcceptsTheUsualForms(string $json, bool $expected): void
    {
        $body = JsonBody::fromRequest($this->request('{"agreeTerms": ' . $json . '}'));

        self::assertSame($expected, $body->bool("agreeTerms"));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonBooleanProvider(): iterable
    {
        yield "text" => ['"sim"'];
        yield "decimal" => ["1.5"];
        yield "number" => ["2"];
        yield "array" => ['["x"]'];
    }

    #[DataProvider("nonBooleanProvider")]
    public function testNonBooleanValueIsA400WithTheFieldName(string $json): void
    {
        $body = JsonBody::fromRequest($this->request('{"agreeTerms": ' . $json . '}'));

        try {
            $body->bool("agreeTerms");
            self::fail("A non-boolean agreeTerms should be rejected");
        } catch (UserValidationException $exception) {
            self::assertSame(400, $exception->getCode());
            self::assertSame(["AgreeTerms: This value should be of type bool."], $exception->getErrors());
        }
    }

    private function request(string $content): Request
    {
        return Request::create(
            uri: "/pt_BR/api/register",
            method: "POST",
            server: ["CONTENT_TYPE" => "application/json"],
            content: $content,
        );
    }
}
