<?php

declare(strict_types = 1);

namespace App\Application\Image\Shared\Validation;

use Attribute;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

/**
 * Caminho de arquivo de imagem do TMDB, como ele aparece nos campos
 * poster_path/backdrop_path/file_path: uma barra, um nome de arquivo e uma
 * extensão de imagem ("/kqjL17yufvn9OVLyXYpvtyrFfak.jpg").
 *
 * Aceita só jpg, jpeg, png, webp e gif. SVG fica de fora de propósito:
 * a API devolve a imagem pelo próprio domínio (format=binary), e um SVG
 * servido assim pode executar script.
 *
 * NOTA (out/2026): criado na reescrita do módulo Image. A mesma
 * validação existia em quatro lugares, com regras diferentes (o
 * GetImageRequest com regex, e ImageController, ImageOriginalController e
 * ImageInfoController com um validateEndpoint() privado cada um, que
 * respondia 400 por um caminho e 500 por outro). Agora GetImageRequest e
 * GetImageInfoRequest usam esta constraint, e o erro é sempre 400 com o
 * campo "endpoint".
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class ImageEndpoint extends Compound {
    /**
     * @param array<string, mixed> $options
     *
     * @return \Symfony\Component\Validator\Constraint[]
     */
    protected function getConstraints(array $options): array {
        return [
            new Assert\NotBlank(message: 'Image endpoint cannot be blank'),
            new Assert\Length(
                max: 500,
                maxMessage: 'Image endpoint cannot be longer than {{ limit }} characters',
            ),
            new Assert\Regex(
                pattern: '/^\/[a-zA-Z0-9_\-.]+\.(jpg|jpeg|png|webp|gif)$/i',
                message: 'Invalid image endpoint format (expected "/<file>.jpg|jpeg|png|webp|gif")',
            ),
            new Assert\Regex(
                pattern: '/\.\./',
                message: 'Image endpoint cannot contain ".."',
                match: false,
            ),
        ];
    }
}
