<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Exception;

use App\Domain\Exception\BusinessLogicException;
use App\Domain\Exception\UserValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Regressões de dois bugs das exceções de domínio que viravam 500.
 *
 * NOTA (out/2026):
 * - withContext() fazia `clone $this`, e Exception não é clonável no PHP
 *   (Error "Trying to clone an uncloneable object"): todo erro de
 *   validação de imagem que usava withContext() respondia 500.
 * - multipleValidationErrors() aplicava ucfirst() à chave; com uma lista
 *   (chaves 0, 1...) isso é TypeError sob strict_types — outro 500 nos
 *   erros de imagem.
 */
final class DomainExceptionTest extends TestCase
{
    public function testWithContextMergesIntoTheSameInstance(): void
    {
        $exception = BusinessLogicException::notFound("Movie", 550);

        $returned = $exception->withContext(["source" => "tmdb"]);

        self::assertSame($exception, $returned);
        self::assertSame(
            ["resource" => "Movie", "identifier" => 550, "source" => "tmdb"],
            $exception->getContext(),
        );
    }

    public function testMultipleValidationErrorsAcceptsNamedAndListKeys(): void
    {
        $exception = UserValidationException::multipleValidationErrors([
            "movie_id" => "Movie ID must be between 1 and 999999999",
            0 => "Unsupported image format",
        ]);

        self::assertSame(400, $exception->getCode());
        self::assertSame(
            ["Movie_id: Movie ID must be between 1 and 999999999", "Unsupported image format"],
            $exception->getErrors(),
        );
    }
}
