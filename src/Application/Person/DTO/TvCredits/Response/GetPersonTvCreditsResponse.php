<?php

declare(strict_types = 1);

namespace App\Application\Person\DTO\TvCredits\Response;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do GetPersonTvCreditsUseCase: o `data` de
 * GET /person/{personId}/tv/credits — as séries em que a pessoa atuou
 * (`cast`) e em que trabalhou na equipe técnica (`crew`).
 *
 * As propriedades públicas (fora as #[Ignore]) são o `data` da resposta;
 * o GetPersonTvCreditsOutput expõe este objeto inteiro em `data`.
 *
 * NOTA (out/2026): criado com o endpoint de créditos de TV, que antes era
 * só um placeholder sempre null no GetPersonDetailsUseCase. Espelha o
 * GetPersonMovieCreditsResponse ({"person_id", "cast", "crew"}).
 */
final class GetPersonTvCreditsResponse {
    /**
     * @param int                                                                     $personId
     * @param list<\App\Application\Person\DTO\TvCredits\Response\Model\TvCastCredit> $cast
     * @param list<\App\Application\Person\DTO\TvCredits\Response\Model\TvCrewCredit> $crew
     * @param string|null                                                             $message
     */
    public function __construct(
        #[SerializedName('person_id')]
        public private(set) int $personId {
            /**
             * @return int
             */ get => $this->personId;
        },
        public private(set) array $cast {
            /**
             * @return list<\App\Application\Person\DTO\TvCredits\Response\Model\TvCastCredit>
             */ get => $this->cast;
        },
        public private(set) array $crew {
            /**
             * @return list<\App\Application\Person\DTO\TvCredits\Response\Model\TvCrewCredit>
             */ get => $this->crew;
        },
        // NOTA: message é envelope — o GetPersonTvCreditsOutput já a expõe
        // no nível dele; #[Ignore] evita repeti-la dentro de `data`.
        #[Ignore]
        public private(set) ?string $message = null {
            /**
             * @return string|null
             */ get => $this->message;
        },
    ) {
    }

    /**
     * @param int                                                                     $personId
     * @param list<\App\Application\Person\DTO\TvCredits\Response\Model\TvCastCredit> $cast
     * @param list<\App\Application\Person\DTO\TvCredits\Response\Model\TvCrewCredit> $crew
     * @param string|null                                                             $message
     *
     * @return self
     */
    public static function success(
        int $personId,
        array $cast,
        array $crew,
        ?string $message = null,
    ): self {
        return new self(
            personId: $personId,
            cast: $cast,
            crew: $crew,
            message: $message,
        );
    }
}
