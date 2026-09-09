<?php

declare(strict_types=1);

namespace App\Application\Movie\DTO;

use Symfony\Component\Serializer\Attribute\Ignore;
/**
 * Response DTO for movie videos operations
 *
 * This DTO represents the response from getting movie videos (trailers, teasers, etc.).
 * It will be automatically mapped to ApiPlatform Output Models via the auto-mapping system.
 */
final class GetMovieVideosResponse
{
    /**
     * @param bool        $success
     * @param array       $videos
     * @param int         $movieId
     * @param array       $results
     * @param string|null $message
     * @param array       $errors
     */
    public function __construct(
        #[Ignore]
        public private(set) bool $success,
        public private(set) array $videos,
        public private(set) int $movieId,
        public private(set) array $results,
        #[Ignore]
        public private(set) ?string $message = null,
        #[Ignore]
        public private(set) array $errors = [],
    ) {}

    public static function success(
        array $videos,
        int $movieId,
        array $results = [],
    ): self {
        return new self(
            success: true,
            videos: $videos,
            movieId: $movieId,
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
            videos: [],
            movieId: $movieId,
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

    public function getVideos(): array
    {
        return $this->videos;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    // trailers/teasers/clips continuam SEM #[Ignore] de propósito — são o
    // objetivo do endpoint, não vazamento de detalhe interno
    public array $trailers {
        get => $this->filterVideosByType('trailer');
    }

    public array $teasers {
        get => $this->filterVideosByType('teaser');
    }

    public array $clips {
        get => $this->filterVideosByType('clip');
    }

    public function getTrailers(): array
    {
        return $this->trailers;
    }

    public function getTeasers(): array
    {
        return $this->teasers;
    }

    public function getClips(): array
    {
        return $this->clips;
    }

    private function filterVideosByType(string $type): array
    {
        return array_values(
            array_filter(
                $this->videos,
                static fn(array $video) => strtolower($video['type'] ?? '') === $type,
            ),
        );
    }

    public array $asArray {
        get {
            return [
                'success' => $this->success,
                'data' => [
                    'movie_id' => $this->movieId,
                    'videos' => $this->videos,
                    'results' => $this->results,
                    'trailers' => $this->trailers,
                    'teasers' => $this->teasers,
                    'clips' => $this->clips,
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
