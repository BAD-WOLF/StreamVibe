<?php

declare(strict_types = 1);

namespace App\Infrastructure\Image;

use App\Application\Image\Port\ImageProcessorInterface;
use Psr\Log\LoggerInterface;

/**
 * Implementação da ImageProcessorInterface com fileinfo (tipo MIME) e GD
 * (recompressão JPEG). As duas extensões estão nas imagens Docker de dev
 * e teste (Dockerfile.minimal e Dockerfile.test) e passam a estar na de
 * produção (docker/Dockerfile).
 *
 * NOTA (out/2026): a lógica veio do GetImageUseCase
 * (detectMimeType()/compressImage()), com duas correções:
 * - sem GD, imagecreatefromstring() não existe e a chamada era Error (não
 *   Exception), que escapava do catch do UseCase e virava 500; agora a
 *   imagem volta sem recompressão e o aviso vai para o log;
 * - imagedestroy() saiu: desde o PHP 8.0 a imagem é um objeto GdImage
 *   liberado sozinho, e a função não faz mais nada.
 */
final readonly class GdImageProcessor implements ImageProcessorInterface {
    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function detectMimeType(string $content): ?string {
        if ($content === '') {
            return null;
        }

        if (function_exists('finfo_buffer')) {
            $mimeType = new \finfo(FILEINFO_MIME_TYPE)->buffer($content);

            if (is_string($mimeType) && $mimeType !== '') {
                return $mimeType;
            }
        }

        // Sem fileinfo: assinatura dos formatos que o TMDB serve.
        return match (true) {
            str_starts_with($content, "\xFF\xD8\xFF") => 'image/jpeg',
            str_starts_with($content, "\x89PNG\x0D\x0A\x1A\x0A") => 'image/png',
            str_starts_with($content, 'GIF87a'), str_starts_with($content, 'GIF89a') => 'image/gif',
            str_starts_with($content, 'RIFF') && substr($content, 8, 4) === 'WEBP' => 'image/webp',
            default => null,
        };
    }

    /**
     * {@inheritDoc}
     */
    public function recompressJpeg(string $content, int $quality): string {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
            $this->logger->warning('GD extension not available; image returned without recompression', [
                'quality' => $quality,
            ]);

            return $content;
        }

        $image = @imagecreatefromstring($content);

        if ($image === false) {
            $this->logger->warning('Image could not be decoded for recompression', [
                'quality' => $quality,
                'content_length' => strlen($content),
            ]);

            return $content;
        }

        ob_start();
        $written = imagejpeg($image, null, $quality);
        $recompressed = (string) ob_get_clean();

        if (!$written || $recompressed === '') {
            $this->logger->warning('JPEG recompression failed', [
                'quality' => $quality,
            ]);

            return $content;
        }

        return $recompressed;
    }
}
