<?php

declare(strict_types = 1);

namespace App\Application\Shared;

use App\Domain\Exception\UserValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Valida um Request DTO (atributos #[Assert\...]) e lança
 * UserValidationException quando há violações. Existe para que cada
 * UseCase não precise repetir o bloco de validação.
 *
 * Os erros são indexados pelo nome do campo em snake_case
 * (ex.: "movie_id"), que é o que
 * UserValidationException::multipleValidationErrors() espera — ele aplica
 * ucfirst() à chave, então chaves numéricas causariam TypeError sob
 * strict_types.
 *
 * O contexto extra ($context) vai só para o log, não para a exceção.
 *
 * NOTA (out/2026): o NOTA antigo dizia que DomainException::withContext()
 * não podia ser usado por fazer `clone $this` (Exception não é
 * clonável). Isso foi corrigido no próprio withContext(), que hoje altera
 * a instância; o motivo para não usá-lo aqui é só que o contexto é de
 * diagnóstico (log), e não precisa ir no corpo de erro.
 */
final readonly class RequestValidator {
    /**
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @param \Psr\Log\LoggerInterface                                  $logger
     */
    public function __construct(
        private ValidatorInterface $validator,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param object               $request           DTO com constraints #[Assert\...]
     * @param string               $validationContext identifica a origem (ex.: "movie_images_request")
     * @param array<string, mixed> $context           dados extras para o log (ex.: movie_id)
     *
     * @return void
     * @throws \App\Domain\Exception\UserValidationException
     */
    public function validate(
        object $request,
        string $validationContext,
        array $context = [],
    ): void {
        $violations = $this->validator->validate($request);

        if (count($violations) === 0) {
            return;
        }

        $this->logger->warning('Request validation failed', $context + [
                'validation_context' => $validationContext,
                'violations_count' => count($violations),
            ]);

        $fieldErrors = [];
        foreach ($violations as $violation) {
            $field = $this->toFieldName($violation->getPropertyPath());
            $message = (string) $violation->getMessage();

            $fieldErrors[$field] = isset($fieldErrors[$field])
                ? $fieldErrors[$field] . '; ' . $message
                : $message;
        }

        throw UserValidationException::multipleValidationErrors($fieldErrors);
    }

    /**
     * movieId -> movie_id (nome do campo como aparece na API).
     *
     * @param string $propertyPath
     *
     * @return string
     */
    private function toFieldName(string $propertyPath): string {
        if ($propertyPath === '') {
            return 'request';
        }

        return strtolower(
            (string) preg_replace('/(?<!^)[A-Z]/', '_$0', $propertyPath),
        );
    }
}
