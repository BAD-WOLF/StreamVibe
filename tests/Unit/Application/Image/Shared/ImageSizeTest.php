<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Image\Shared;

use App\Application\Image\Shared\ImageSize;
use PHPUnit\Framework\TestCase;

/**
 * ImageSize: as três formas do catálogo de tamanhos (BY_TYPE, ALL e
 * ROUTE_REQUIREMENT) precisam continuar batendo. Elas são escritas por
 * extenso porque constante de classe não aceita chamada de função.
 *
 * NOTA (out/2026): criado com o catálogo único. Antes eram três listas
 * soltas e diferentes — GET /images/sizes anunciava w45 e w300 e a rota
 * de imagem respondia 404 para eles.
 */
final class ImageSizeTest extends TestCase
{
    public function testAllIsTheUnionOfTheSizesPerType(): void
    {
        $union = array_values(array_unique(array_merge(...array_values(ImageSize::BY_TYPE))));

        $expected = $union;
        $actual = ImageSize::ALL;
        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
        self::assertSame(count(ImageSize::ALL), count(array_unique(ImageSize::ALL)), "ALL has duplicates");
    }

    public function testRouteRequirementAcceptsExactlyTheSizesOfAll(): void
    {
        self::assertSame(implode("|", ImageSize::ALL), ImageSize::ROUTE_REQUIREMENT);

        foreach (ImageSize::ALL as $size) {
            self::assertMatchesRegularExpression("#^(" . ImageSize::ROUTE_REQUIREMENT . ")$#", $size);
        }

        self::assertDoesNotMatchRegularExpression("#^(" . ImageSize::ROUTE_REQUIREMENT . ")$#", "w999");
    }

    public function testEveryTypeOffersTheOriginalSize(): void
    {
        foreach (ImageSize::BY_TYPE as $type => $sizes) {
            self::assertContains(ImageSize::ORIGINAL, $sizes, "{$type} has no original size");
        }
    }
}
