<?php

declare(strict_types = 1);

namespace App\Application\Person\DTO\MovieCredits\Response;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do GetPersonMovieCreditsUseCase: o `data` de
 * GET /person/{personId}/movie/credits — os filmes em que a pessoa atuou
 * (`cast`) e em que trabalhou na equipe técnica (`crew`).
 *
 * As propriedades públicas (fora as #[Ignore]) são o `data` da resposta;
 * o GetPersonMovieCreditsOutput expõe este objeto inteiro em `data`.
 *
 * NOTA (out/2026):
 * - Movido de DTO/Credits/ para DTO/MovieCredits/Response/ (ver NOTA do
 *   GetPersonMovieCreditsRequest).
 * - Ganhou `person_id`, como `movie_id` nos endpoints /movies/{id}/*.
 * - cast/crew deixaram de ser ?array (null nunca foi um valor válido no
 *   sucesso) e cada item virou MovieCastCredit/MovieCrewCredit.
 * - Saíram success/errors, failure(), toArray() e os getters de
 *   compatibilidade (isSuccess(), getCast(), getTotalCredits()...): erro
 *   agora é exceção (400/404/502/503 pelo ExceptionListener) — antes
 *   pessoa inexistente e TMDB fora do ar viravam "success": false com
 *   status 400 —, e o JSON sai do GetPersonMovieCreditsOutput. O toArray()
 *   era o JSON real até aqui (o auto-mapping nunca rodava), por isso o
 *   formato de `data` mudou: {"cast", "crew"} → {"person_id", "cast",
 *   "crew"}.
 */
final class GetPersonMovieCreditsResponse {
    /**
     * @param int                                                                           $personId
     * @param list<\App\Application\Person\DTO\MovieCredits\Response\Model\MovieCastCredit> $cast
     * @param list<\App\Application\Person\DTO\MovieCredits\Response\Model\MovieCrewCredit> $crew
     * @param string|null                                                                   $message
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
             * @return list<\App\Application\Person\DTO\MovieCredits\Response\Model\MovieCastCredit>
             */ get => $this->cast;
        },
        public private(set) array $crew {
            /**
             * @return list<\App\Application\Person\DTO\MovieCredits\Response\Model\MovieCrewCredit>
             */ get => $this->crew;
        },
        // NOTA: message é envelope — o GetPersonMovieCreditsOutput já a
        // expõe no nível dele; #[Ignore] evita repeti-la dentro de `data`.
        #[Ignore]
        public private(set) ?string $message = null {
            /**
             * @return string|null
             */ get => $this->message;
        },
    ) {
    }

    /**
     * @param int                                                                           $personId
     * @param list<\App\Application\Person\DTO\MovieCredits\Response\Model\MovieCastCredit> $cast
     * @param list<\App\Application\Person\DTO\MovieCredits\Response\Model\MovieCrewCredit> $crew
     * @param string|null                                                                   $message
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
