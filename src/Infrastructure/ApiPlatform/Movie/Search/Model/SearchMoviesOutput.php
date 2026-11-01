<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movie\Search\Model;

use App\Application\Movie\DTO\Search\Response\SearchMoviesResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/movies/search/{query}/{page}:
 *
 *     {
 *       "success": true,
 *       "data": {
 *         "movies": [{"id": 268, "title": "Batman", "poster_url": "https://image.tmdb.org/t/p/w500/...", ...}],
 *         "current_page": 1,
 *         "total_pages": 42,
 *         "total_results": 830,
 *         "has_next": true,
 *         "has_previous": false,
 *         "next_page": 2,
 *         "previous_page": null
 *       },
 *       "message": "Found 830 movies for 'batman'"
 *     }
 *
 * Mapeado do SearchMoviesResponse pelo auto-mapping (#[AutoMapFromDto] +
 * GenericOutputMapper): `data` recebe o DTO inteiro — as propriedades
 * públicas dele são o conteúdo de `data` — e `message` vem de
 * SearchMoviesResponse::$message. O controller serializa este objeto e o
 * Swagger documenta esta mesma classe, então resposta e documentação não
 * têm como divergir.
 *
 * NOTA (out/2026):
 * - Era Movies/Search/Model/SearchMoviesOutput, compartilhado pelas cinco
 *   listas (busca, tendência, populares, mais bem avaliados e em cartaz).
 *   O OutputMapperRegistry aceita um Output por DTO, então cada lista
 *   ganhou DTO e Output próprios; o formato de `data` é o mesmo nas sete
 *   listas de filmes.
 * - `success` virou propriedade fixa (true) fora do construtor: erro não
 *   passa por aqui (sai do ExceptionListener com "success": false), e o
 *   DTO deixou de carregar o envelope.
 * - fromArray() saiu: ninguém chamava, e passaria um array onde o
 *   construtor exige o DTO (TypeError).
 */
#[AutoMapFromDto(SearchMoviesResponse::class)]
final readonly class SearchMoviesOutput {
    /**
     * Sempre true nesta classe (ver NOTA acima).
     */
    public bool $success;

    /**
     * @param \App\Application\Movie\DTO\Search\Response\SearchMoviesResponse $data
     * @param string|null                                                    $message
     */
    public function __construct(
        public SearchMoviesResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
