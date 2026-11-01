<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movie\Popular\Model;

use App\Application\Movie\DTO\Popular\Response\GetPopularMoviesResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/movies/popular:
 *
 *     {
 *       "success": true,
 *       "data": {
 *         "movies": [{"id": 1184918, "title": "...", ...}],
 *         "current_page": 1,
 *         "total_pages": 500,
 *         "total_results": 10000,
 *         "has_next": true,
 *         "has_previous": false,
 *         "next_page": 2,
 *         "previous_page": null
 *       },
 *       "message": "Popular movies retrieved successfully"
 *     }
 *
 * Mapeado do GetPopularMoviesResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de GetPopularMoviesResponse::$message. É esta classe
 * que o controller serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): criado na separação do antigo SearchMoviesOutput (que
 * servia às cinco listas e era mapeado de um único DTO). `success` é fixo
 * (true): erro sai do ExceptionListener.
 */
#[AutoMapFromDto(GetPopularMoviesResponse::class)]
final readonly class GetPopularMoviesOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Movie\DTO\Popular\Response\GetPopularMoviesResponse $data
     * @param string|null                                                         $message
     */
    public function __construct(
        public GetPopularMoviesResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
