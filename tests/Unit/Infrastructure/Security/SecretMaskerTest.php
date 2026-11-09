<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Security;

use App\Infrastructure\Security\SecretMasker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * SecretMasker: os segredos da aplicação trocados pelo valor (inteiros, ou
 * só a senha de um DSN; também escapados para URL e JSON), e o que tem
 * cara de segredo trocado pela forma (senha numa URL, Bearer, JWT, PEM,
 * chave=valor e "chave": "valor").
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class SecretMaskerTest extends TestCase
{
    public function testTheSecretsOfTheApplicationAreMaskedByValue(): void
    {
        $masker = new SecretMasker([
            'APP_SECRET' => '9f3c1a7e5b2d4f60',
            'TMDB_TOKEN' => 'tmdb-token-value-123',
        ]);

        self::assertSame(
            'secret *** and token *** in a sentence',
            $masker->mask('secret 9f3c1a7e5b2d4f60 and token tmdb-token-value-123 in a sentence'),
        );
    }

    public function testOnlyThePasswordOfADsnIsMasked(): void
    {
        $masker = new SecretMasker(['DATABASE_URL' => 'postgresql://app:S3cr3t%40pw@database:5432/app?serverVersion=16']);

        // decodificada (como o driver a mostra) e codificada (como está na URL)
        self::assertSame('password *** rejected for app on database', $masker->mask('password S3cr3t@pw rejected for app on database'));
        self::assertSame('dsn ***', $masker->mask('dsn S3cr3t%40pw'));
    }

    public function testADsnWithoutPasswordHidesItsUser(): void
    {
        $masker = new SecretMasker(['SENTRY_DSN' => 'https://a1b2c3d4e5f6@o42.ingest.sentry.io/7']);

        self::assertSame('https://***@o42.ingest.sentry.io/7', $masker->mask('https://a1b2c3d4e5f6@o42.ingest.sentry.io/7'));
    }

    public function testASecretIsFoundAlsoEscapedInJsonAndInUrls(): void
    {
        $masker = new SecretMasker(['JWT_PASSPHRASE' => 'pass/"phrase"&more']);

        self::assertSame('{"value":"***"}', $masker->mask('{"value":"pass\/\"phrase\"&more"}'));
        self::assertSame('{"value":"***"}', $masker->mask('{"value":"pass/\"phrase\"&more"}'));
        self::assertSame('?q=***', $masker->mask('?q=' . rawurlencode('pass/"phrase"&more')));
    }

    public function testALongerSecretIsMaskedWhole(): void
    {
        $masker = new SecretMasker(['A' => 'abcdef', 'B' => 'abcdef-ghijkl']);

        self::assertSame('*** and ***', $masker->mask('abcdef-ghijkl and abcdef'));
    }

    public function testShortOrMissingValuesAreNotMaskedByValue(): void
    {
        $masker = new SecretMasker(['JWT_PASSPHRASE' => 'test', 'SENTRY_DSN' => 'null', 'MAILER_DSN' => null, 'OTHER' => 42]);

        self::assertSame('a test run, null, 42', $masker->mask('a test run, null, 42'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function shapeProvider(): iterable
    {
        yield 'password in a URL' => ['smtp://mailer:p4ssw0rd@mailpit:1025 failed', 'smtp://mailer:***@mailpit:1025 failed'];
        yield 'empty user in a URL' => ['redis://:p4ssw0rd@cache:6379', 'redis://:***@cache:6379'];
        yield 'bearer header' => ['Authorization: Bearer abc.def-ghi_jkl', 'Authorization: Bearer ***'];
        yield 'basic header' => ['authorization: basic dXNlcjpwYXNz', 'authorization: basic ***'];
        yield 'JWT' => ['token eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjMifQ.c2lnbmF0dXJl here', 'token *** here'];
        yield 'option with =' => ['php bin/console x --password=hunter22 --verbose', 'php bin/console x --password=*** --verbose'];
        yield 'environment variable' => ['APP_SECRET=0123abcd TMDB_TOKEN=xyz', 'APP_SECRET=*** TMDB_TOKEN=***'];
        yield 'query string' => ['/reset?token=abc123&next=home', '/reset?token=***&next=home'];
        yield 'JSON key' => ['{"password":"hunter\"22","new_password": "x","api_key":"k","name":"ana"}', '{"password":"***","new_password": "***","api_key":"***","name":"ana"}'];
        yield 'private key' => ["-----BEGIN RSA PRIVATE KEY-----\nMIIEow\nIBAAKC\n-----END RSA PRIVATE KEY-----", '-----BEGIN RSA PRIVATE KEY-----***-----END RSA PRIVATE KEY-----'];
        yield 'public key stays' => ['-----BEGIN PUBLIC KEY-----MIIB-----END PUBLIC KEY-----', '-----BEGIN PUBLIC KEY-----MIIB-----END PUBLIC KEY-----'];
        yield 'nothing secret' => ['GET /pt_BR/api/movies/550 200 12ms', 'GET /pt_BR/api/movies/550 200 12ms'];
        yield 'empty' => ['', ''];
    }

    #[DataProvider('shapeProvider')]
    public function testWhatLooksLikeASecretIsMaskedByItsShape(string $text, string $expected): void
    {
        self::assertSame($expected, new SecretMasker([])->mask($text));
    }
}
