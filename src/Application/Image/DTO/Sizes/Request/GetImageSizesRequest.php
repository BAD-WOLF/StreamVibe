<?php

declare(strict_types = 1);

namespace App\Application\Image\DTO\Sizes\Request;

/**
 * Entrada do GetImageSizesUseCase: os exemplos de uso que a resposta deve
 * trazer, já como URLs da API.
 *
 * Quem monta os exemplos é o controller, com o router (a partir dos nomes
 * das rotas de imagem e do locale da requisição): assim eles não têm como
 * divergir das rotas reais. O UseCase só os devolve.
 *
 * NOTA (out/2026): criado para corrigir os usage_examples, que eram
 * strings fixas no UseCase sem o prefixo /{_locale} (ex.:
 * "/api/image/w500/poster.jpg") — nenhum deles funcionava.
 */
final class GetImageSizesRequest {
    /**
     * @param array<string, string> $usageExamples nome do exemplo => URL (path + query)
     */
    public function __construct(
        public private(set) array $usageExamples {
            /**
             * @return array<string, string>
             */ get => $this->usageExamples;
        },
    ) {
    }
}
