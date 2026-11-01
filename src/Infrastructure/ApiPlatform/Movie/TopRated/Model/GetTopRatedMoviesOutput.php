<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movie\TopRated\Model;

use App\Application\Movie\DTO\TopRated\Response\GetTopRatedMoviesResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/movies/top-rated:
 *
 *     {
 *       "success": true,
 *       "data": {
 *         "movies": [{"id": 238, "title": "The Godfather", ...}],
 *         "current_page": 1,
 *         "total_pages": 500,
 *         "total_results": 10000,
 *         "has_next": true,
 *         "has_previous": false,
 *         "next_page": 2,
 *         "previous_page": null
 *       },
 *       "message": "Top rated movies retrieved successfully"
 *     }
 *
 * Mapeado do GetTopRatedMoviesResponse pelo auto-mapping
 * (#[AutoMapFromDto] + GenericOutputMapper): `data` recebe o DTO inteiro
 * e `message` vem de GetTopRatedMoviesResponse::$message. É esta classe
 * que o controller serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): criado na separação do antigo SearchMoviesOutput (que
 * servia às cinco listas e era mapeado de um único DTO). `success` é fixo
 * (true): erro sai do ExceptionListener.
 */
#[AutoMapFromDto(GetTopRatedMoviesResponse::class)]
final readonly class GetTopRatedMoviesOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Movie\DTO\TopRated\Response\GetTopRatedMoviesResponse $data
     * @param string|null                                                           $message
     */
    public function __construct(
        public GetTopRatedMoviesResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
