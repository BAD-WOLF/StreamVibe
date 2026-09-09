<?php

declare(strict_types=1);

namespace App\Application\Movie\DTO;

use Symfony\Component\Serializer\Attribute\Ignore;
/**
 * Response DTO for similar movies operations
 *
 * This DTO represents the response from getting similar movies.
 * It will be automatically mapped to ApiPlatform Output Models via the auto-mapping system.
 */
final class GetMovieSimilarResponse
{
    public function __construct(
        #[Ignore]
        public private(set) bool $success,
        public private(set) array $similar,
        public private(set) int $movieId,
        public private(set) int $page,
        public private(set) int $totalPages,
        public private(set) int $totalResults,
        public private(set) array $results,
        #[Ignore]
        public private(set) ?string $message = null,
        #[Ignore]
        public private(set) array $errors = [],
    ) {}

    public static function success(
        array $similar,
        int $movieId,
        int $page = 1,
        int $totalPages = 1,
        int $totalResults = 0,
        array $results = [],
    ): self {
        return new self(
            success: true,
            similar: $similar,
            movieId: $movieId,
            page: $page,
            totalPages: $totalPages,
            totalResults: $totalResults,
            results: $results,
        );
    }

    public static function error(
        string $message,
        array $errors = [],
        int $movieId = 0,
    ): self {
        return new self(
            success: false,
            similar: [],
            movieId: $movieId,
            page: 1,
            totalPages: 1,
            totalResults: 0,
            results: [],
            message: $message,
            errors: $errors,
        );
    }

    #[Ignore]
    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getMovieId(): int
    {
        return $this->movieId;
    }

    public function getSimilar(): array
    {
        return $this->similar;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    public function getTotalResults(): int
    {
        return $this->totalResults;
    }

    #[Ignore]
    public function hasNext(): bool
    {
        return $this->page < $this->totalPages;
    }

    #[Ignore]
    public function hasPrevious(): bool
    {
        return $this->page > 1;
    }

    #[Ignore]
    public function getNextPage(): ?int
    {
        return $this->hasNext() ? $this->page + 1 : null;
    }

    #[Ignore]
    public function getPreviousPage(): ?int
    {
        return $this->hasPrevious() ? $this->page - 1 : null;
    }

    public array $asArray {
        get {
            return [
                'success' => $this->success,
                'data' => [
                    'movie_id' => $this->movieId,
                    'similar' => $this->similar,
                    'results' => $this->results,
                    'pagination' => [
                        'page' => $this->page,
                        'total_pages' => $this->totalPages,
                        'total_results' => $this->totalResults,
                        'has_next' => $this->hasNext(),
                        'has_previous' => $this->hasPrevious(),
                        'next_page' => $this->getNextPage(),
                        'previous_page' => $this->getPreviousPage(),
                    ],
                ],
                'message' => $this->message,
                'errors' => $this->errors,
            ];
        }
    }

    public function toArray(): array
    {
        return $this->asArray;
    }
}
