<?php

declare(strict_types = 1);

namespace App\Application\Person\DTO\Details\Request;

use App\Application\Shared\Validation\LanguageCode;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrada do GetPersonDetailsUseCase: o id da pessoa no TMDB e o idioma
 * opcional do conteúdo (biografia).
 *
 * NOTA (ago/2026): includeMovieCredits/includeTvCredits/includeImages/
 * includeExternalIds foram removidos. Movie credits ganhou endpoint
 * dedicado (GET /person/{id}/movie/credits). Os outros três eram
 * placeholders que sempre devolviam null (nunca implementados) — código
 * morto, sem chamada real à TMDB.
 *
 * NOTA (out/2026):
 * - Movido de DTO/Details/ para DTO/Details/Request/, espelhando o módulo
 *   Movie (Request e Response em pastas próprias). Os três que nunca
 *   foram implementados viraram endpoints próprios: TvCredits, Images e
 *   ExternalIds.
 * - `language` passou a ser validado (LanguageCode) e repassado ao TMDB;
 *   antes era aceito e ignorado.
 * - `region` saiu: o endpoint de pessoa do TMDB não tem esse parâmetro,
 *   então ele nunca teria efeito.
 * - asArray, toArray() e os getters de compatibilidade (getPersonId()...)
 *   saíram: quem usa lê as propriedades direto.
 */
final class GetPersonDetailsRequest {
    /**
     * @param int         $personId
     * @param string|null $language
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

        #[LanguageCode]
        public private(set) ?string $language = null {
            /**
             * @return string|null
             */ get => $this->language;
        },
    ) {
    }
}
