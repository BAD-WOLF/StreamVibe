<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Details\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 */
final class GetMovieDetailsRequest {
    /**
     * @param int         $movieId
     * @param string|null $language
     * @param string|null $region
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Movie ID cannot be blank')]
        #[
            Assert\Range(
                notInRangeMessage: "Movie ID must be between {{ min }} and {{ max }}",
                min: 1,
                max: 999999999,
            ),
        ]
        public private(set) int $movieId {
            /**
             * @return int
             */ get => $this->movieId;
        },

        public private(set) ?string $language = null {
            /**
             * @return string|null
             */ get => $this->language;
        },

        public private(set) ?string $region = null {
            /**
             * @return string|null
             */ get => $this->region;
        },
    ) {
    }

    // Virtual computed property using property hooks
    public array $asArray {
        /**
         * @return array
         */
        get {
            $data = [
                'movie_id' => $this->movieId,
            ];

            if ($this->language !== null) {
                $data['language'] = $this->language;
            }

            if ($this->region !== null) {
                $data['region'] = $this->region;
            }

            return $data;
        }
    }

    /**
     * @return int
     */
    public function getMovieId(): int {
        return $this->movieId;
    }

    /**
     * @return string|null
     */
    public function getLanguage(): ?string {
        return $this->language;
    }

    /**
     * @return string|null
     */
    public function getRegion(): ?string {
        return $this->region;
    }

    /**
     * @return array
     */
    public function toArray(): array {
        return $this->asArray;
    }
}
