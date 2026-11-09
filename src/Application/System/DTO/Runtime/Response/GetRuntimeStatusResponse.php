<?php

declare(strict_types = 1);

namespace App\Application\System\DTO\Runtime\Response;

use App\Application\System\DTO\Runtime\Response\Model\OpcacheInfo;
use App\Application\System\DTO\Runtime\Response\Model\PhpInfo;
use App\Application\System\DTO\Runtime\Response\Model\SymfonyInfo;
use Symfony\Component\Serializer\Attribute\Ignore;

/**
 * Resposta do GetRuntimeStatusUseCase (GET /system/runtime): o PHP, o
 * OPcache e o Symfony que atendem a API.
 *
 *     {"php": {"version": "8.4.14", "sapi": "fpm-fcgi", ...},
 *      "opcache": {"enabled": true, "hits": 581230, ...},
 *      "symfony": {"version": "7.4.16", "environment": "prod", ...}}
 *
 * - opcache: null quando a extensão não está instalada (o contêiner de dev,
 *   docker/Dockerfile.minimal, não a instala).
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class GetRuntimeStatusResponse {
    /**
     * @param \App\Application\System\DTO\Runtime\Response\Model\PhpInfo          $php
     * @param \App\Application\System\DTO\Runtime\Response\Model\OpcacheInfo|null $opcache
     * @param \App\Application\System\DTO\Runtime\Response\Model\SymfonyInfo      $symfony
     * @param string|null                                                         $message
     */
    public function __construct(
        public private(set) PhpInfo $php {
            /**
             * @return \App\Application\System\DTO\Runtime\Response\Model\PhpInfo
             */ get => $this->php;
        },
        public private(set) ?OpcacheInfo $opcache {
            /**
             * @return \App\Application\System\DTO\Runtime\Response\Model\OpcacheInfo|null
             */ get => $this->opcache;
        },
        public private(set) SymfonyInfo $symfony {
            /**
             * @return \App\Application\System\DTO\Runtime\Response\Model\SymfonyInfo
             */ get => $this->symfony;
        },
        #[Ignore]
        public private(set) ?string $message = null {
            /**
             * @return string|null
             */ get => $this->message;
        },
    ) {
    }

    /**
     * @param \App\Application\System\DTO\Runtime\Response\Model\PhpInfo          $php
     * @param \App\Application\System\DTO\Runtime\Response\Model\OpcacheInfo|null $opcache
     * @param \App\Application\System\DTO\Runtime\Response\Model\SymfonyInfo      $symfony
     * @param string|null                                                         $message
     *
     * @return self
     */
    public static function success(
        PhpInfo $php,
        ?OpcacheInfo $opcache,
        SymfonyInfo $symfony,
        ?string $message = null,
    ): self {
        return new self(
            php: $php,
            opcache: $opcache,
            symfony: $symfony,
            message: $message,
        );
    }
}
