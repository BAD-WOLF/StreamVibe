<?php

declare(strict_types = 1);

namespace App\Application\Shared\Validation;

use Attribute;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

/**
 * Código de idioma aceito pelo TMDB no parâmetro `language`: ISO 639-1,
 * com região ISO 3166-1 opcional ("pt", "pt-BR", "en-US"). Null passa
 * (o parâmetro é opcional).
 *
 * NOTA (out/2026): criado quando `language` passou a ser repassado ao
 * TMDB de verdade (antes era aceito e ignorado). É um Compound para que
 * todos os Request DTOs de conteúdo usem exatamente a mesma regra.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class LanguageCode extends Compound {
    /**
     * @param array<string, mixed> $options
     *
     * @return \Symfony\Component\Validator\Constraint[]
     */
    protected function getConstraints(array $options): array {
        return [
            new Assert\Regex(
                pattern: '/^[a-z]{2}(-[A-Z]{2})?$/',
                message: 'Language must be an ISO 639-1 code, optionally followed by a region (e.g. "pt-BR")',
            ),
        ];
    }
}
