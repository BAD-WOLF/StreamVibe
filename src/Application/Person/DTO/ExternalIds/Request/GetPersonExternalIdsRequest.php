<?php

declare(strict_types = 1);

namespace App\Application\Person\DTO\ExternalIds\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do GetPersonExternalIdsUseCase: o id da pessoa no TMDB.
 *
 * NOTA (out/2026): criado com o endpoint de ids externos de pessoa
 * (GET /person/{personId}/external/ids), que antes era só um placeholder
 * sempre null no GetPersonDetailsUseCase. Sem `language`: ids externos não
 * dependem de idioma.
 */
final class GetPersonExternalIdsRequest {
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
}
