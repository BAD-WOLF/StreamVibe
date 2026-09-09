<?php

declare(strict_types = 1);

namespace App\Application\Person\DTO\Details;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * NOTA (ago/2026): includeMovieCredits/includeTvCredits/includeImages/
 * includeExternalIds foram removidos. Movie credits ganhou endpoint
 * dedicado (GET /person/{id}/movie/credits). Os outros três eram
 * placeholders que sempre devolviam null (nunca implementados) — código
 * morto, sem chamada real à TMDB.
 */
final class GetPersonDetailsRequest {
    /**
     * @param int         $personId
     * @param string|null $language
     * @param string|null $region
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Person ID cannot be blank')]
        #[
            Assert\Range(
                notInRangeMessage: "Person ID must be between {{ min }} and {{ max }}",
                min: 1,
                max: 999999999,
            ),
        ]
        public private(set) int $personId {
            /**
             * @return int
             */ get => $this->personId;
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
                'person_id' => $this->personId,
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

    // Compatibility methods - these can be removed once all calling code is updated

    /**
     * @return int
     */
    public function getPersonId(): int {
        return $this->personId;
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
