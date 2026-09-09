<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movies\Recommendations\Model;

use App\Application\Movie\DTO\GetMovieRecommendationsResponse as GetMovieRecommendationsResponseDto;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;
use Symfony\Component\Serializer\Attribute\Ignore;

/**
 * Output model for movie recommendations operations in ApiPlatform
 *
 * NOTA (ago/2026): movie_id/recommendations/total_results/total_pages/
 * page removidos — duplicavam data.movieId/data.recommendations/
 * data.totalResults/data.totalPages (mesmo dado, duas vezes na mesma
 * resposta). fieldMap saiu junto. Getters restaurados com #[Ignore]
 * (sem bug real, podem ser úteis depois — só precisam de #[Ignore]
 * pra não vazar de novo no schema, mesmo problema que corrigimos no
 * DTO). fromArray() ficou fora por ora: reconstruir o data
 * aninhado a partir de array cru exigiria duplicar a lógica de
 * GetMovieRecommendationsUseCase — decisão em aberto, não removida
 * por engano.
 */
#[AutoMapFromDto(GetMovieRecommendationsResponseDto::class)]
final readonly class RecommendationsOutput {
    public function __construct(
        public bool $success,
        public GetMovieRecommendationsResponseDto $data,
        public ?string $message = null,
        public array $errors = [],
    ) {
    }

    #[Ignore]
    public function isSuccess(): bool {
        return $this->success;
    }

    #[Ignore]
    public function getData(): GetMovieRecommendationsResponseDto {
        return $this->data;
    }

    #[Ignore]
    public function getMessage(): ?string {
        return $this->message;
    }

    #[Ignore]
    public function getErrors(): array {
        return $this->errors;
    }

    #[Ignore]
    public function toArray(): array {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'message' => $this->message,
            'errors' => $this->errors,
        ];
    }
}