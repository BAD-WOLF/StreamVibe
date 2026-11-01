<?php

declare(strict_types = 1);

namespace App\Application\Image\Port;

/**
 * Porta de processamento de imagem usada pelo GetImageUseCase: descobrir
 * o tipo real dos bytes e recomprimir JPEG. A implementação
 * (Infrastructure\Image\GdImageProcessor) usa fileinfo e GD.
 *
 * NOTA (out/2026): antes o GetImageUseCase chamava finfo_buffer() e
 * imagejpeg() direto — o Application dependia das extensões do PHP e não
 * dava para testar o UseCase sem elas. Mesmo raciocínio da
 * MovieDatabaseGatewayInterface (o UseCase fala com uma porta, a
 * Infrastructure implementa).
 */
interface ImageProcessorInterface {
    /**
     * Tipo MIME detectado a partir dos bytes (não da extensão).
     *
     * @param string $content bytes da imagem
     *
     * @return string|null "image/jpeg", "image/png"...; null se não for possível detectar
     */
    public function detectMimeType(string $content): ?string;

    /**
     * Recomprime um JPEG com a qualidade pedida. Se não conseguir (GD
     * ausente, bytes inválidos), devolve os bytes originais.
     *
     * @param string $content bytes de um JPEG
     * @param int    $quality 1 a 100
     *
     * @return string bytes do JPEG recomprimido
     */
    public function recompressJpeg(string $content, int $quality): string;
}
