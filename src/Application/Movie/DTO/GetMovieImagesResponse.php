<?php

declare(strict_types=1);

namespace App\Application\Movie\DTO;

/**
 * Response DTO for movie images operations
 *
 * This DTO represents the response from getting movie images (posters, backdrops, logos).
 * It will be automatically mapped to ApiPlatform Output Models via the auto-mapping system.
 */
final class GetMovieImagesResponse
{
    /**
     * @param bool        $success
     * @param array       $images
     * @param int         $movieId
     * @param array       $backdrops
     * @param array       $posters
     * @param array       $logos
     * @param string|null $message
     * @param array       $errors
     */
    public function __construct(
        public private(set) bool $success,
        public private(set) array $images,
        public private(set) int $movieId,
        public private(set) array $backdrops,
        public private(set) array $posters,
        public private(set) array $logos,
        public private(set) ?string $message = null,
        public private(set) array $errors = [],
    ) {}

    /**
     * Create a successful response
     *
     * @param array $images
     * @param int   $movieId
     * @param array $backdrops
     * @param array $posters
     * @param array $logos
     *
     * @return self
     */
    public static function success(
        array $images,
        int $movieId,
        array $backdrops = [],
        array $posters = [],
        array $logos = [],
    ): self {
        return new self(
            success: true,
            images: $images,
            movieId: $movieId,
            backdrops: $backdrops,
            posters: $posters,
            logos: $logos,
        );
    }

    /**
     * Create an error response
     *
     * @param string $message
     * @param array  $errors
     * @param int    $movieId
     *
     * @return self
     */
    public static function error(
        string $message,
        array $errors = [],
        int $movieId = 0,
    ): self {
        return new self(
            success: false,
            images: [],
            movieId: $movieId,
            backdrops: [],
            posters: [],
            logos: [],
            message: $message,
            errors: $errors,
        );
    }

    /**
     * Check if the response represents a successful operation
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Get response message
     *
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Get errors array
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get movie ID
     *
     * @return int
     */
    public function getMovieId(): int
    {
        return $this->movieId;
    }

    /**
     * Get images data
     *
     * @return array
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * Get backdrops data
     *
     * @return array
     */
    public function getBackdrops(): array
    {
        return $this->backdrops;
    }

    /**
     * Get posters data
     *
     * @return array
     */
    public function getPosters(): array
    {
        return $this->posters;
    }

    /**
     * Get logos data
     *
     * @return array
     */
    public function getLogos(): array
    {
        return $this->logos;
    }

    /**
     * Convert response to array format
     *
     * @return array
     */
    public array $asArray {
        get {
            return [
                'success' => $this->success,
                'data' => [
                    'movie_id' => $this->movieId,
                    'images' => $this->images,
                    'backdrops' => $this->backdrops,
                    'posters' => $this->posters,
                    'logos' => $this->logos,
                ],
                'message' => $this->message,
                'errors' => $this->errors,
            ];
        }
    }

    /**
     * Legacy toArray method for backwards compatibility
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->asArray;
    }
}
