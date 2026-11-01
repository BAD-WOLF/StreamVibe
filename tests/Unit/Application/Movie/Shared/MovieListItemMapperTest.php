<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Movie\Shared;

use App\Application\Movie\Shared\MovieListItemMapper;
use App\Application\Shared\DateYearExtractor;
use App\Application\Shared\RatingPercentageCalculator;
use App\Application\Shared\TmdbImageUrlBuilder;
use App\Domain\Exception\ExternalServiceException;
use PHPUnit\Framework\TestCase;

/**
 * MovieListItemMapper: o item de filme das 7 listas (busca, tendência,
 * populares, mais bem avaliados, em cartaz, similares e recomendações).
 *
 * NOTA (out/2026): criado com o mapper único. O caso da sinopse acentuada
 * é o bug do substr(): cortar por bytes partia um caractere UTF-8 ao meio,
 * e o json_encode da resposta inteira falhava (500).
 */
final class MovieListItemMapperTest extends TestCase
{
    public function testMapsTheTmdbFieldsAndTheDerivedOnes(): void
    {
        $item = $this->mapper()->mapOne([
            "id" => 550,
            "title" => "Clube da Luta",
            "original_title" => "Fight Club",
            "overview" => "Um funcionário de escritório insone...",
            "release_date" => "1999-10-15",
            "poster_path" => "/poster.jpg",
            "backdrop_path" => "/backdrop.jpg",
            "vote_average" => 8.4,
            "vote_count" => 30000,
            "popularity" => 61.4,
            "genre_ids" => [18, "53"],
            "adult" => false,
            "video" => false,
            "original_language" => "en",
        ]);

        self::assertSame(550, $item->id);
        self::assertSame("Clube da Luta", $item->title);
        self::assertSame([18, 53], $item->genre_ids);
        self::assertSame("https://image.tmdb.org/t/p/w500/poster.jpg", $item->poster_url);
        self::assertSame("https://image.tmdb.org/t/p/w1280/backdrop.jpg", $item->backdrop_url);
        self::assertSame("https://image.tmdb.org/t/p/w342/poster.jpg", $item->thumbnail_url);
        self::assertSame(84, $item->rating_percentage);
        self::assertSame(1999, $item->release_year);
        self::assertSame("Um funcionário de escritório insone...", $item->formatted_overview);
    }

    public function testMissingOptionalFieldsBecomeNull(): void
    {
        $item = $this->mapper()->mapOne(["id" => 1, "title" => "Untitled"]);

        self::assertNull($item->release_date);
        self::assertNull($item->poster_url);
        self::assertNull($item->backdrop_url);
        self::assertNull($item->release_year);
        self::assertNull($item->formatted_overview);
        self::assertSame(0, $item->rating_percentage);
    }

    public function testLongAccentedOverviewIsCutWithoutBreakingUtf8(): void
    {
        $overview = str_repeat("Ação é ótima. ", 40);

        $item = $this->mapper()->mapOne(["id" => 1, "title" => "Filme", "overview" => $overview]);

        $formatted = (string) $item->formatted_overview;
        self::assertTrue(mb_check_encoding($formatted, "UTF-8"), "formatted_overview is not valid UTF-8");
        self::assertSame(250, mb_strlen($formatted));
        self::assertStringEndsWith("...", $formatted);
        self::assertNotFalse(json_encode($item, JSON_THROW_ON_ERROR));
    }

    public function testPageWithoutResultsIsAnInvalidTmdbResponse(): void
    {
        $this->expectException(ExternalServiceException::class);
        $this->expectExceptionCode(502);

        $this->mapper()->mapPage(["status_message" => "?"], 1, "TMDB popular movies");
    }

    public function testPageKeepsThePagination(): void
    {
        $page = $this->mapper()->mapPage(
            ["page" => 3, "total_pages" => 10, "total_results" => 200, "results" => [["id" => 1, "title" => "A"]]],
            3,
            "TMDB popular movies",
        );

        self::assertSame(3, $page->page);
        self::assertSame(10, $page->totalPages);
        self::assertSame(200, $page->totalResults);
        self::assertCount(1, $page->movies);
    }

    private function mapper(): MovieListItemMapper
    {
        return new MovieListItemMapper(
            new TmdbImageUrlBuilder(),
            new DateYearExtractor(),
            new RatingPercentageCalculator(),
        );
    }
}
