<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movie\Trending\Model;

use App\Application\Movie\DTO\Trending\Response\GetTrendingMoviesResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/movies/trending/{timeWindow}:
 *
 *     {
 *       "success": true,
 *       "data": {
 *         "time_window": "day",
 *         "movies": [{"id": 1184918, "title": "...", ...}],
 *         "current_page": 1,
 *         "total_pages": 500,
 *         "total_results": 10000,
 *         "has_next": true,
 *         "has_previous": false,
 *         "next_page": 2,
 *         "previous_page": null
 *       },
 *       "message": "Trending movies retrieved successfully"
 *     }
 *
 * Mapeado do GetTrendingMoviesResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de GetTrendingMoviesResponse::$message. É esta classe
 * que o controller serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): criado na separação do antigo SearchMoviesOutput (que
 * servia às cinco listas). `data.time_window` mostra a janela usada — o
 * bug do {timeWindow} ignorado (sempre "week") era invisível na resposta.
 * `success` é fixo (true): erro sai do ExceptionListener.
 */
#[AutoMapFromDto(GetTrendingMoviesResponse::class)]
final readonly class GetTrendingMoviesOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Movie\DTO\Trending\Response\GetTrendingMoviesResponse $data
     * @param string|null                                                           $message
     */
    public function __construct(
        public GetTrendingMoviesResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
