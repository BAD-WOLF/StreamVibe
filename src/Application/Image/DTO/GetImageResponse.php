<?php

declare(strict_types = 1);

namespace App\Application\Image\DTO;

/**
 *
 */
final class GetImageResponse {
    /**
     * @param bool        $success
     * @param string|null $imageData
     * @param string|null $mimeType
     * @param string|null $url
     * @param int|null    $size
     * @param string|null $format
     * @param string|null $message
     * @param array       $errors
     * @param array|null  $metadata
     */
    public function __construct(
        public private(set) bool $success {
            /**
             * @return bool
             */ get => $this->success;
        },
        public private(set) ?string $imageData = null {
            /**
             * @return string|null
             */ get => $this->imageData;
        },
        public private(set) ?string $mimeType = null {
            /**
             * @return string|null
             */ get => $this->mimeType;
        },
        public private(set) ?string $url = null {
            /**
             * @return string|null
             */ get => $this->url;
        },
        public private(set) ?int $size = null {
            /**
             * @return int|null
             */ get => $this->size;
        },
        public private(set) ?string $format = null {
            /**
             * @return string|null
             */ get => $this->format;
        },
        public private(set) ?string $message = null {
            /**
             * @return string|null
             */ get => $this->message;
        },
        public private(set) array $errors = [] {
            /**
             * @return array
             */ get => $this->errors;
        },
        public private(set) ?array $metadata = null {
            /**
             * @return array|null
             */ get => $this->metadata;
        },
    ) {
    }

    /**
     * @param string      $imageData
     * @param string      $mimeType
     * @param string      $format
     * @param int|null    $size
     * @param string|null $url
     * @param array|null  $metadata
     * @param string|null $message
     *
     * @return self
     */
    public static function success(
        string $imageData,
        string $mimeType,
        string $format,
        ?int $size = null,
        ?string $url = null,
        ?array $metadata = null,
        ?string $message = null,
    ): self {
        return new self(
            success: true,
            imageData: $imageData,
            mimeType: $mimeType,
            url: $url,
            size: $size,
            format: $format,
            message: $message,
            metadata: $metadata,
        );
    }

    /**
     * @param string $message
     * @param array  $errors
     *
     * @return self
     */
    public static function failure(string $message, array $errors = []): self {
        return new self(success: false, message: $message, errors: $errors);
    }

    /**
     * @param string      $url
     * @param string|null $message
     *
     * @return self
     */
    public static function url(string $url, ?string $message = null): self {
        return new self(
            success: true,
            url: $url,
            format: 'url',
            message: $message,
        );
    }

    // Virtual computed properties using property hooks
    public bool $isSuccess {
        /**
         * @return bool
         */
        get => $this->success;
    }

    public bool $hasErrors {
        /**
         * @return bool
         */
        get => !empty($this->errors);
    }

    public bool $isBase64 {
        /**
         * @return bool
         */
        get => $this->format === 'base64';
    }

    public bool $isBinary {
        /**
         * @return bool
         */
        get => $this->format === 'binary';
    }

    public bool $isUrl {
        /**
         * @return bool
         */
        get => $this->format === 'url';
    }

    public ?string $base64WithPrefix {
        /**
         * @return string|null
         */
        get {
            if (!$this->isBase64 || !$this->imageData || !$this->mimeType) {
                return null;
            }

            return "data:{$this->mimeType};base64,{$this->imageData}";
        }
    }

    public ?int $contentLength {
        /**
         * @return int|null
         */
        get {
            if (!$this->imageData) {
                return null;
            }

            if ($this->isBase64) {
                return (int)((strlen($this->imageData) * 3) / 4);
            }

            return strlen($this->imageData);
        }
    }

    public ?string $fileExtension {
        /**
         * @return string|null
         */
        get {
            if (!$this->mimeType) {
                return null;
            }

            return match ($this->mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
                'image/svg+xml' => 'svg',
                default => null,
            };
        }
    }

    public bool $hasValidImageData {
        /**
         * @return bool
         */
        get {
            return $this->success &&
                (($this->isBase64 && !empty($this->imageData)) ||
                    ($this->isBinary && !empty($this->imageData)) ||
                    ($this->isUrl && !empty($this->url)));
        }
    }

    public ?string $formattedSize {
        /**
         * @return string|null
         */
        get {
            if (!$this->size) {
                return null;
            }

            $units = ['B', 'KB', 'MB', 'GB'];
            $size = $this->size;
            $unitIndex = 0;

            while ($size >= 1024 && $unitIndex < count($units) - 1) {
                $size /= 1024;
                $unitIndex++;
            }

            return round($size, 2).' '.$units[$unitIndex];
        }
    }

    public array $asArray {
        /**
         * @return array
         */
        get {
            $data = [
                'success' => $this->success,
                'message' => $this->message,
                'errors' => $this->errors,
            ];

            if ($this->success) {
                $data['data'] = [
                    'format' => $this->format,
                    'mime_type' => $this->mimeType,
                    'size' => $this->size,
                    'content_length' => $this->contentLength,
                    'file_extension' => $this->fileExtension,
                ];

                if ($this->isUrl) {
                    $data['data']['url'] = $this->url;
                } elseif ($this->isBase64) {
                    $data['data']['base64'] = $this->base64WithPrefix;
                    $data['data']['image_data'] = $this->imageData;
                } elseif ($this->isBinary) {
                    $data['data']['binary_length'] = strlen($this->imageData ?? '');
                }

                if ($this->metadata) {
                    $data['data']['metadata'] = $this->metadata;
                }
            }

            return $data;
        }
    }

    public array $asJsonResponse {
        /**
         * @return array
         */
        get {
            if ($this->isBase64) {
                return [
                    'success' => $this->success,
                    'base64' => $this->base64WithPrefix,
                    'mime_type' => $this->mimeType,
                    'size' => $this->size,
                    'message' => $this->message,
                ];
            }

            if ($this->isUrl) {
                return [
                    'success' => $this->success,
                    'url' => $this->url,
                    'message' => $this->message,
                ];
            }

            return $this->asArray;
        }
    }

    // Compatibility methods - these can be removed once all calling code is updated

    /**
     * @return bool
     */
    public function isSuccess(): bool {
        return $this->isSuccess;
    }

    /**
     * @return string|null
     */
    public function getImageData(): ?string {
        return $this->imageData;
    }

    /**
     * @return string|null
     */
    public function getMimeType(): ?string {
        return $this->mimeType;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string {
        return $this->url;
    }

    /**
     * @return int|null
     */
    public function getSize(): ?int {
        return $this->size;
    }

    /**
     * @return string|null
     */
    public function getFormat(): ?string {
        return $this->format;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string {
        return $this->message;
    }

    /**
     * @return array
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * @return array|null
     */
    public function getMetadata(): ?array {
        return $this->metadata;
    }

    /**
     * @return bool
     */
    public function hasErrors(): bool {
        return $this->hasErrors;
    }

    /**
     * @return bool
     */
    public function isBase64(): bool {
        return $this->isBase64;
    }

    /**
     * @return bool
     */
    public function isBinary(): bool {
        return $this->isBinary;
    }

    /**
     * @return bool
     */
    public function isUrl(): bool {
        return $this->isUrl;
    }

    /**
     * @return string|null
     */
    public function getBase64WithPrefix(): ?string {
        return $this->base64WithPrefix;
    }

    /**
     * @return int|null
     */
    public function getContentLength(): ?int {
        return $this->contentLength;
    }

    /**
     * @return string|null
     */
    public function getFileExtension(): ?string {
        return $this->fileExtension;
    }

    /**
     * @return array
     */
    public function toArray(): array {
        return $this->asArray;
    }

    /**
     * @return array
     */
    public function toJsonResponse(): array {
        return $this->asJsonResponse;
    }

    /**
     * @return bool
     */
    public function hasValidImageData(): bool {
        return $this->hasValidImageData;
    }

    /**
     * @return string|null
     */
    public function getFormattedSize(): ?string {
        return $this->formattedSize;
    }
}
