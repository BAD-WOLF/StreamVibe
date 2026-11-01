<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movie\Images\Model;

use App\Application\Movie\DTO\Images\Response\GetMovieImagesResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;

/**
 * Corpo de sucesso de GET /{_locale}/api/movies/{movieId}/images:
 *
 *     {
 *       "success": true,
 *       "data": {
 *         "movie_id": 550,
 *         "backdrops": [{"file_path": "/...jpg", "width": 1920, "height": 1080, "url_w780": "https://...", ...}],
 *         "posters": [...],
 *         "logos": [...]
 *       },
 *       "message": "Movie images retrieved successfully"
 *     }
 *
 * Mapeado do GetMovieImagesResponse pelo auto-mapping (#[AutoMapFromDto]
 * + GenericOutputMapper): `data` recebe o DTO inteiro e `message` vem de
 * GetMovieImagesResponse::$message. É esta classe que o controller
 * serializa e que o Swagger documenta.
 *
 * NOTA (out/2026): era Movies/Images/Model/ImagesOutput. Até aqui só
 * alimentava o Swagger — o JSON real saía do toArray() do DTO, porque o
 * registry nunca chegava ao controller. Renomeado para espelhar o
 * DTO/UseCase/Controller (GetMovieImages*). Saíram `errors` (sempre vazio
 * no sucesso), os getters de envelope e fromArray(); `success` virou fixo
 * (true).
 */
#[AutoMapFromDto(GetMovieImagesResponse::class)]
final readonly class GetMovieImagesOutput {
    /**
     * Sempre true nesta classe (erros saem do ExceptionListener).
     */
    public bool $success;

    /**
     * @param \App\Application\Movie\DTO\Images\Response\GetMovieImagesResponse $data
     * @param string|null                                                      $message
     */
    public function __construct(
        public GetMovieImagesResponse $data,
        public ?string $message = null,
    ) {
        $this->success = true;
    }
}
