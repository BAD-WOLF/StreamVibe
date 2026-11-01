<?php

declare(strict_types = 1);

namespace App\Application\Person\DTO\Images\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do GetPersonImagesUseCase: o id da pessoa no TMDB.
 *
 * NOTA (out/2026): criado com o endpoint de imagens de pessoa
 * (GET /person/{personId}/images), que antes era só um placeholder sempre
 * null no GetPersonDetailsUseCase. Sem `language`: o endpoint de imagens
 * de pessoa do TMDB não tem esse parâmetro.
 */
final class GetPersonImagesRequest {
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
