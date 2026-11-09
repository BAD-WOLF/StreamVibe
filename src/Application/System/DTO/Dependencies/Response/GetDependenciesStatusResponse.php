<?php

declare(strict_types = 1);

namespace App\Application\System\DTO\Dependencies\Response;

use App\Application\System\DTO\Dependencies\Response\Model\DatabaseStatus;
use App\Application\System\DTO\Dependencies\Response\Model\ExternalServiceStatus;
use Symfony\Component\Serializer\Attribute\Ignore;

/**
 * Resposta do GetDependenciesStatusUseCase (GET /system/dependencies): o
 * banco de dados e o TMDB, conferidos na hora.
 *
 *     {"database": {"reachable": true, "latency_ms": 0.84, ...},
 *      "tmdb": {"reachable": true, "latency_ms": 182.4, "status_code": 200, "error": null}}
 *
 * O TMDB é consultado em GET /3/authentication, que só confere o token
 * (TMDB_TOKEN), com 5 segundos de limite.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class GetDependenciesStatusResponse {
    /**
     * @param \App\Application\System\DTO\Dependencies\Response\Model\DatabaseStatus        $database
     * @param \App\Application\System\DTO\Dependencies\Response\Model\ExternalServiceStatus $tmdb
     * @param string|null                                                                   $message
     */
    public function __construct(
        public private(set) DatabaseStatus $database {
            /**
             * @return \App\Application\System\DTO\Dependencies\Response\Model\DatabaseStatus
             */ get => $this->database;
        },
        public private(set) ExternalServiceStatus $tmdb {
            /**
             * @return \App\Application\System\DTO\Dependencies\Response\Model\ExternalServiceStatus
             */ get => $this->tmdb;
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
     * @param \App\Application\System\DTO\Dependencies\Response\Model\DatabaseStatus        $database
     * @param \App\Application\System\DTO\Dependencies\Response\Model\ExternalServiceStatus $tmdb
     * @param string|null                                                                   $message
     *
     * @return self
     */
    public static function success(
        DatabaseStatus $database,
        ExternalServiceStatus $tmdb,
        ?string $message = null,
    ): self {
        return new self(
            database: $database,
            tmdb: $tmdb,
            message: $message,
        );
    }
}
