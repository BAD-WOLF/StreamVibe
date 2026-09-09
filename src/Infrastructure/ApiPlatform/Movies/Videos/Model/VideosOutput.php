<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movies\Videos\Model;

use App\Application\Movie\DTO\GetMovieVideosResponse as GetMovieVideosResponseDto;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;
use Symfony\Component\Serializer\Attribute\Ignore;

/**
 * Output model for movie videos operations in ApiPlatform
 *
 * NOTA (ago/2026): mesma limpeza e mesmo raciocínio de
 * RecommendationsOutput — ver comentário lá.
 */
#[AutoMapFromDto(GetMovieVideosResponseDto::class)]
final readonly class VideosOutput {
    public function __construct(
        public bool $success,
        public GetMovieVideosResponseDto $data,
        public ?string $message = null,
        public array $errors = [],
    ) {
    }

    #[Ignore]
    public function isSuccess(): bool {
        return $this->success;
    }

    #[Ignore]
    public function getData(): GetMovieVideosResponseDto {
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
