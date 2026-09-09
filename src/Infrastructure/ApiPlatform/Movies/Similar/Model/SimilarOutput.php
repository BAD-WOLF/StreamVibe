<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movies\Similar\Model;

use App\Application\Movie\DTO\GetMovieSimilarResponse as GetMovieSimilarResponseDto;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;
use Symfony\Component\Serializer\Attribute\Ignore;

/**
 * Output model for similar movies operations in ApiPlatform
 *
 * NOTA (ago/2026): mesma limpeza e mesmo raciocínio de
 * RecommendationsOutput — ver comentário lá.
 */
#[AutoMapFromDto(GetMovieSimilarResponseDto::class)]
final readonly class SimilarOutput {
    public function __construct(
        public bool $success,
        public GetMovieSimilarResponseDto $data,
        public ?string $message = null,
        public array $errors = [],
    ) {
    }

    #[Ignore]
    public function isSuccess(): bool {
        return $this->success;
    }

    #[Ignore]
    public function getData(): GetMovieSimilarResponseDto {
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
