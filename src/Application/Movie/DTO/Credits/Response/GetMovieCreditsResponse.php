<?php

declare(strict_types = 1);

namespace App\Application\Movie\DTO\Credits\Response;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Response DTO for movie credits operations
 *
 * This DTO represents the response from getting movie credits (cast and crew).
 * It is automatically mapped to the GetMovieCreditsOutput ApiPlatform
 * Output Model via the auto-mapping system.
 *
 * NOTA (out/2026): com o auto-mapping ligado, as propriedades públicas
 * (fora as #[Ignore]) são exatamente o `data` da resposta:
 * movie_id, cast e crew. Antes o JSON real vinha do toArray() — sem
 * envelope e com cast_count/crew_count/has_director/directors, que só
 * repetiam o que já está em cast/crew. `id` virou `movie_id`, como nos
 * outros endpoints /movies/{id}/*. Saíram também as propriedades virtuais
 * internas (hasCast, mainCast, directors, crewByDepartment...), o `errors`
 * (sempre vazio), isSuccess(), getMessage(), getErrors() e toArray():
 * ninguém os usava.
 */
final class GetMovieCreditsResponse {
    /**
     * NOTA (bug corrigido): nativeType removido dos #[ApiProperty] —
     * Type::object()/Type::list() são chamadas de método, não
     * expressões constantes, e argumentos de atributo só aceitam
     * expressões constantes (Fatal error: "Constant expression contains
     * invalid operations"). O tipo dos itens de cast/crew segue
     * documentado nos @param abaixo. (out/2026: os #[ApiProperty] que
     * sobraram, vazios, também saíram — não mudavam nada no Swagger e
     * prendiam o Application ao API Platform.)
     *
     * NOTA (message): é parte do "envelope" da resposta, que o
     * GetMovieCreditsOutput já expõe no nível dele. Por isso leva
     * #[Ignore] aqui: sem isso apareceria repetido também dentro de `data`.
     *
     * @param int                                                                $movieId
     * @param list<\App\Application\Movie\DTO\Credits\Response\Model\CastMember> $cast
     * @param list<\App\Application\Movie\DTO\Credits\Response\Model\CrewMember> $crew
     * @param string|null                                                        $message
     */
    public function __construct(
        #[SerializedName('movie_id')]
        public private(set) int $movieId {
            get => $this->movieId;
        },
        public private(set) array $cast {
            get => $this->cast;
        },
        public private(set) array $crew {
            get => $this->crew;
        },
        #[Ignore]
        public private(set) ?string $message = null {
            get => $this->message;
        },
    ) {
    }

    /**
     * Create a successful response
     *
     * @param int                                                                $movieId
     * @param list<\App\Application\Movie\DTO\Credits\Response\Model\CastMember> $cast
     * @param list<\App\Application\Movie\DTO\Credits\Response\Model\CrewMember> $crew
     * @param string|null                                                        $message
     *
     * @return self
     */
    public static function success(
        int $movieId,
        array $cast,
        array $crew,
        ?string $message = null,
    ): self {
        return new self(
            movieId: $movieId,
            cast: $cast,
            crew: $crew,
            message: $message,
        );
    }
}
