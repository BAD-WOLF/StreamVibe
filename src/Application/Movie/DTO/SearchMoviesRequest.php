<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 */
final class SearchMoviesRequest {
    /**
     * @param string      $query
     * @param int         $page
     * @param bool        $includeAdult
     * @param string|null $region
     * @param int|null    $year
     * @param int|null    $primaryReleaseYear
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Search query cannot be blank')]
        #[
            Assert\Length(
                min: 1,
                max: 255,
                minMessage: 'Search query must be at least {{ limit }} character long',
                maxMessage: 'Search query cannot be longer than {{ limit }} characters',
            ),
        ]
        public private(set) string $query {
            /**
             * @return string
             */ get => $this->query;
        },

        #[
            Assert\Range(
                notInRangeMessage: "Page must be between {{ min }} and {{ max }}",
                min: 1,
                max: 1000,
            ),
        ]
        public private(set) int $page = 1 {
            /**
             * @return int
             */ get => $this->page;
        },

        public private(set) bool $includeAdult = false {
            /**
             * @return bool
             */ get => $this->includeAdult;
        },

        public private(set) ?string $region = null {
            /**
             * @return string|null
             */ get => $this->region;
        },

        public private(set) ?int $year = null {
            /**
             * @return int|null
             */ get => $this->year;
        },

        public private(set) ?int $primaryReleaseYear = null {
            /**
             * @return int|null
             */ get => $this->primaryReleaseYear;
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
                'query' => $this->query,
                'page' => $this->page,
                'include_adult' => $this->includeAdult,
            ];

            if ($this->region !== null) {
                $data['region'] = $this->region;
            }

            if ($this->year !== null) {
                $data['year'] = $this->year;
            }

            if ($this->primaryReleaseYear !== null) {
                $data['primary_release_year'] = $this->primaryReleaseYear;
            }

            return $data;
        }
    }

    // Compatibility methods - these can be removed once all calling code is updated

    /**
     * @return string
     */
    public function getQuery(): string {
        return $this->query;
    }

    /**
     * @return int
     */
    public function getPage(): int {
        return $this->page;
    }

    /**
     * @return bool
     */
    public function shouldIncludeAdult(): bool {
        return $this->includeAdult;
    }

    /**
     * @return string|null
     */
    public function getRegion(): ?string {
        return $this->region;
    }

    /**
     * @return int|null
     */
    public function getYear(): ?int {
        return $this->year;
    }

    /**
     * @return int|null
     */
    public function getPrimaryReleaseYear(): ?int {
        return $this->primaryReleaseYear;
    }

    /**
     * @return array
     */
    public function toArray(): array {
        return $this->asArray;
    }
}
