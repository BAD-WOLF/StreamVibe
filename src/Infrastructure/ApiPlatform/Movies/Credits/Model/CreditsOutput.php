<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Movies\Credits\Model;

use App\Application\Movie\DTO\Credits\Response\GetMovieCreditsResponse;
use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;
use Symfony\Component\Serializer\Attribute\Ignore;

/**
 * Output model for movie credits operations in ApiPlatform
 *
 * NOTA (ago/2026): movie_id/cast/crew/credits removidos — duplicavam
 * data.id/data.cast/data.crew (mesmo dado, duas vezes na mesma
 * resposta); credits nunca teve de onde vir. fieldMap saiu junto.
 * Getters restaurados com #[Ignore] (sem bug real, podem ser úteis
 * depois — só precisam de #[Ignore] pra não vazar de novo no schema,
 * mesmo problema que corrigimos no DTO). fromArray() ficou fora por
 * ora: reconstruir o data aninhado a partir de array cru exigiria
 * duplicar a lógica de GetMovieCreditsUseCase — decisão em aberto,
 * não removida por engano.
 */
#[AutoMapFromDto(GetMovieCreditsResponse::class)]
final readonly class CreditsOutput {
    /**
     * @param bool                                                                $success
     * @param \App\Application\Movie\DTO\Credits\Response\GetMovieCreditsResponse $data
     * @param string|null                                                         $message
     * @param array                                                               $errors
     */
    public function __construct(
        public bool $success,
        public GetMovieCreditsResponse $data,
        public ?string $message = null,
        public array $errors = [],
    ) {
    }

    /**
     * @return bool
     */
    #[Ignore]
    public function isSuccess(): bool {
        return $this->success;
    }

    /**
     * @return \App\Application\Movie\DTO\Credits\Response\GetMovieCreditsResponse
     */
    #[Ignore]
    public function getData(): GetMovieCreditsResponse {
        return $this->data;
    }

    /**
     * @return string|null
     */
    #[Ignore]
    public function getMessage(): ?string {
        return $this->message;
    }

    /**
     * @return array
     */
    #[Ignore]
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * @return array
     */
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
