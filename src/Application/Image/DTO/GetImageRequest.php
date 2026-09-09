<?php

declare(strict_types = 1);

namespace App\Application\Image\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 */
final class GetImageRequest {
    /**
     * @param string      $endpoint
     * @param string|null $size
     * @param string      $format
     * @param bool        $cache
     * @param int|null    $quality
     * @param string|null $fallbackUrl
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Image endpoint cannot be blank')]
        #[
            Assert\Length(
                min: 1,
                max: 500,
                minMessage: 'Image endpoint must be at least {{ limit }} character long',
                maxMessage: 'Image endpoint cannot be longer than {{ limit }} characters',
            ),
        ]
        #[
            Assert\Regex(
                pattern: '/^\/[a-zA-Z0-9_\-\.\/]+\.(jpg|jpeg|png|webp|gif)$/i',
                message: 'Invalid image endpoint format',
            ),
        ]
        public private(set) string $endpoint {
            /**
             * @return string
             */ get => $this->endpoint;
        },

        #[
            Assert\Choice(
                choices: [
                    'w92',
                    'w154',
                    'w185',
                    'w342',
                    'w500',
                    'w780',
                    'w1280',
                    'h632',
                    'original',
                ],
                message: 'Invalid image size. Allowed sizes: {{ choices }}',
            ),
        ]
        public private(set) ?string $size = null {
            /**
             * @return string|null
             */ get => $this->size;
        },

        #[
            Assert\Choice(
                choices: ['base64', 'binary', 'url'],
                message: 'Invalid response format. Allowed formats: {{ choices }}',
            ),
        ]
        public private(set) string $format = 'base64' {
            /**
             * @return string
             */ get => $this->format;
        },

        public private(set) bool $cache = true {
            /**
             * @return bool
             */ get => $this->cache;
        },

        public private(set) ?int $quality = null {
            /**
             * @return int|null
             */ get => $this->quality;
        },

        public private(set) ?string $fallbackUrl = null {
            /**
             * @return string|null
             */ get => $this->fallbackUrl;
        },
    ) {
    }

    // Virtual computed properties using property hooks
    public string $effectiveSize {
        /**
         * @return string
         */
        get => $this->size ?? 'original';
    }

    public bool $isBase64Format {
        /**
         * @return bool
         */
        get => $this->format === 'base64';
    }

    public bool $isBinaryFormat {
        /**
         * @return bool
         */
        get => $this->format === 'binary';
    }

    public bool $isUrlFormat {
        /**
         * @return bool
         */
        get => $this->format === 'url';
    }

    public string $tmdbUrl {
        /**
         * @return string
         */
        get => "https://image.tmdb.org/t/p/{$this->effectiveSize}{$this->endpoint}";
    }

    public ?string $fileExtension {
        /**
         * @return mixed|null
         */
        get {
            $pathInfo = pathinfo($this->endpoint);

            return $pathInfo['extension'] ?? null;
        }
    }

    public bool $isValidImageExtension {
        /**
         * @return bool
         */
        get {
            $extension = strtolower($this->fileExtension ?? '');

            return in_array(
                $extension,
                ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                true,
            );
        }
    }

    public array $asArray {
        /**
         * @return array
         */
        get {
            return [
                'endpoint' => $this->endpoint,
                'size' => $this->size,
                'effective_size' => $this->effectiveSize,
                'format' => $this->format,
                'cache' => $this->cache,
                'quality' => $this->quality,
                'fallback_url' => $this->fallbackUrl,
                'tmdb_url' => $this->tmdbUrl,
                'file_extension' => $this->fileExtension,
            ];
        }
    }

    // Compatibility methods - these can be removed once all calling code is updated

    /**
     * @return string
     */
    public function getEndpoint(): string {
        return $this->endpoint;
    }

    /**
     * @return string|null
     */
    public function getSize(): ?string {
        return $this->size;
    }

    /**
     * @return string
     */
    public function getEffectiveSize(): string {
        return $this->effectiveSize;
    }

    /**
     * @return string
     */
    public function getFormat(): string {
        return $this->format;
    }

    /**
     * @return bool
     */
    public function shouldCache(): bool {
        return $this->cache;
    }

    /**
     * @return int|null
     */
    public function getQuality(): ?int {
        return $this->quality;
    }

    /**
     * @return string|null
     */
    public function getFallbackUrl(): ?string {
        return $this->fallbackUrl;
    }

    /**
     * @return bool
     */
    public function isBase64Format(): bool {
        return $this->isBase64Format;
    }

    /**
     * @return bool
     */
    public function isBinaryFormat(): bool {
        return $this->isBinaryFormat;
    }

    /**
     * @return bool
     */
    public function isUrlFormat(): bool {
        return $this->isUrlFormat;
    }

    /**
     * @return string
     */
    public function buildTmdbUrl(): string {
        return $this->tmdbUrl;
    }

    /**
     * @return string|null
     */
    public function getFileExtension(): ?string {
        return $this->fileExtension;
    }

    /**
     * @return bool
     */
    public function isValidImageExtension(): bool {
        return $this->isValidImageExtension;
    }

    /**
     * @return array
     */
    public function toArray(): array {
        return $this->asArray;
    }
}
