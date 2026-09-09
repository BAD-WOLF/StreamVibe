<?php

declare(strict_types = 1);

namespace App\Application\Person\DTO\Credits;

use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 */
final class GetPersonMovieCreditsRequest {
    /**
     * @param int $personId
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
    ) {
    }

    /**
     * @return int
     */
    public function getPersonId(): int {
        return $this->personId;
    }
}
