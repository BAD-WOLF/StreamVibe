<?php

declare(strict_types = 1);

namespace App\Application\Image\DTO\Content\Response;

use App\Application\Image\Shared\ImageFormat;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do GetImageUseCase. O controller decide o que fazer com ela
 * pelo `format`:
 *
 * - base64: devolve este objeto como `data` (via GetImageOutput):
 *       {"endpoint": "/abc.jpg", "size": "w500", "format": "base64",
 *        "mime_type": "image/jpeg", "content_length": 48213,
 *        "url": "https://image.tmdb.org/t/p/w500/abc.jpg",
 *        "base64": "data:image/jpeg;base64,/9j/4AAQ..."}
 * - binary: devolve `content` (os bytes) com Content-Type = mime_type;
 * - url: redirect (302) para `url`.
 *
 * As propriedades públicas (fora as #[Ignore]) são o `data` do formato
 * base64. `content` (os bytes crus) leva #[Ignore]: não é JSON válido e já
 * vai em `base64`.
 *
 * NOTA (out/2026): reescrito junto com o GetImageUseCase.
 * - O JSON real do base64 era o toJsonResponse() (o auto-mapping nunca
 *   rodava): {"success", "base64", "mime_type", "size", "message"}, sem
 *   `data`. Agora é o envelope padrão {"success", "data", "message"}, com
 *   `data` como acima. O antigo `size` (bytes) virou `content_length`, e
 *   `size` passou a ser o tamanho do TMDB (w500, original), o mesmo nome
 *   do parâmetro da rota.
 * - Saíram success/errors, failure() (erro é exceção), o metadata
 *   (calculado e nunca devolvido no base64), as propriedades virtuais,
 *   asArray/asJsonResponse/toArray()/toJsonResponse() e os getters de
 *   compatibilidade.
 * - Três construtores nomeados, um por formato: binary(), base64() e
 *   url().
 */
final class GetImageResponse {
    /**
     * @param string      $endpoint      caminho do arquivo no TMDB
     * @param string      $size          tamanho do TMDB (w500, original...)
     * @param string      $format        um de ImageFormat::ALL
     * @param string|null $mimeType      null no formato url (a imagem não é baixada)
     * @param int|null    $contentLength bytes da imagem devolvida; null no formato url
     * @param string      $url           a imagem no CDN do TMDB
     * @param string|null $base64        data URI; só no formato base64
     * @param string|null $content       bytes da imagem; só no formato binary
     * @param string|null $message
     */
    public function __construct(
        public private(set) string $endpoint {
            /**
             * @return string
             */ get => $this->endpoint;
        },
        public private(set) string $size {
            /**
             * @return string
             */ get => $this->size;
        },
        public private(set) string $format {
            /**
             * @return string
             */ get => $this->format;
        },
        #[SerializedName('mime_type')]
        public private(set) ?string $mimeType {
            /**
             * @return string|null
             */ get => $this->mimeType;
        },
        #[SerializedName('content_length')]
        public private(set) ?int $contentLength {
            /**
             * @return int|null
             */ get => $this->contentLength;
        },
        public private(set) string $url {
            /**
             * @return string
             */ get => $this->url;
        },
        public private(set) ?string $base64 = null {
            /**
             * @return string|null
             */ get => $this->base64;
        },
        #[Ignore]
        public private(set) ?string $content = null {
            /**
             * @return string|null
             */ get => $this->content;
        },
        // NOTA: message é envelope — o GetImageOutput já a expõe no nível
        // dele; #[Ignore] evita repeti-la dentro de `data`.
        #[Ignore]
        public private(set) ?string $message = null {
            /**
             * @return string|null
             */ get => $this->message;
        },
    ) {
    }

    /**
     * A imagem em bytes, para o controller devolver como corpo binário.
     *
     * @param string      $endpoint
     * @param string      $size
     * @param string      $mimeType
     * @param string      $content
     * @param string      $url
     * @param string|null $message
     *
     * @return self
     */
    public static function binary(
        string $endpoint,
        string $size,
        string $mimeType,
        string $content,
        string $url,
        ?string $message = null,
    ): self {
        return new self(
            endpoint: $endpoint,
            size: $size,
            format: ImageFormat::BINARY,
            mimeType: $mimeType,
            contentLength: strlen($content),
            url: $url,
            content: $content,
            message: $message,
        );
    }

    /**
     * A imagem em data URI base64, para o controller devolver como JSON.
     *
     * @param string      $endpoint
     * @param string      $size
     * @param string      $mimeType
     * @param string      $content bytes da imagem (são codificados aqui)
     * @param string      $url
     * @param string|null $message
     *
     * @return self
     */
    public static function base64(
        string $endpoint,
        string $size,
        string $mimeType,
        string $content,
        string $url,
        ?string $message = null,
    ): self {
        return new self(
            endpoint: $endpoint,
            size: $size,
            format: ImageFormat::BASE64,
            mimeType: $mimeType,
            contentLength: strlen($content),
            url: $url,
            base64: sprintf('data:%s;base64,%s', $mimeType, base64_encode($content)),
            message: $message,
        );
    }

    /**
     * Só a URL do TMDB, para o controller redirecionar (a imagem não é
     * baixada).
     *
     * @param string      $endpoint
     * @param string      $size
     * @param string      $url
     * @param string|null $message
     *
     * @return self
     */
    public static function url(
        string $endpoint,
        string $size,
        string $url,
        ?string $message = null,
    ): self {
        return new self(
            endpoint: $endpoint,
            size: $size,
            format: ImageFormat::URL,
            mimeType: null,
            contentLength: null,
            url: $url,
            message: $message,
        );
    }
}
