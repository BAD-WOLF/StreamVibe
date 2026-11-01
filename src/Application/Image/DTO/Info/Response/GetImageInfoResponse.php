<?php

declare(strict_types = 1);

namespace App\Application\Image\DTO\Info\Response;

use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Resposta do GetImageInfoUseCase: o `data` de
 * GET /images/info/{endpoint}?size=
 *
 *     {"endpoint": "/abc.jpg", "size": "w500",
 *      "url": "https://image.tmdb.org/t/p/w500/abc.jpg", "file_extension": "jpg"}
 *
 * As propriedades públicas (fora as #[Ignore]) são o `data` da resposta;
 * o GetImageInfoOutput expõe este objeto inteiro em `data`.
 *
 * NOTA (out/2026): criado na separação do GetImageUseCase. Antes o
 * endpoint devolvia o toArray() do GetImageResponse —
 * {"success", "message", "errors": [], "data": {"format": "url",
 * "mime_type": null, "size": null, "content_length": null,
 * "file_extension": null, "url": ...}} —, quase tudo null porque a imagem
 * não é baixada. Agora `data` traz só o que a info sabe de fato.
 */
final class GetImageInfoResponse {
    /**
     * @param string      $endpoint      caminho do arquivo no TMDB
     * @param string      $size          tamanho do TMDB (w500, original...)
     * @param string      $url           a imagem no CDN do TMDB
     * @param string      $fileExtension extensão do arquivo, em minúsculas
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
        public private(set) string $url {
            /**
             * @return string
             */ get => $this->url;
        },
        #[SerializedName('file_extension')]
        public private(set) string $fileExtension {
            /**
             * @return string
             */ get => $this->fileExtension;
        },
        // NOTA: message é envelope — o GetImageInfoOutput já a expõe no
        // nível dele; #[Ignore] evita repeti-la dentro de `data`.
        #[Ignore]
        public private(set) ?string $message = null {
            /**
             * @return string|null
             */ get => $this->message;
        },
    ) {
    }

    /**
     * @param string      $endpoint
     * @param string      $size
     * @param string      $url
     * @param string      $fileExtension
     * @param string|null $message
     *
     * @return self
     */
    public static function success(
        string $endpoint,
        string $size,
        string $url,
        string $fileExtension,
        ?string $message = null,
    ): self {
        return new self(
            endpoint: $endpoint,
            size: $size,
            url: $url,
            fileExtension: $fileExtension,
            message: $message,
        );
    }
}
