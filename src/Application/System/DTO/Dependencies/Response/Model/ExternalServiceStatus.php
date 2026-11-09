<?php

declare(strict_types = 1);

namespace App\Application\System\DTO\Dependencies\Response\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Um serviço externo (`data.tmdb` de GET /system/dependencies):
 *
 *     {"reachable": true, "latency_ms": 182.4, "status_code": 200, "error": null}
 *
 * - reachable: o serviço respondeu (com qualquer status HTTP);
 * - latency_ms e status_code: da resposta; null sem resposta;
 * - status_code 200 = tudo certo; no TMDB, 401 = TMDB_TOKEN recusado;
 * - error: o que deu errado (sem resposta, a falha de rede); null quando a
 *   resposta foi 2xx.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class ExternalServiceStatus {
    /**
     * @param bool        $reachable
     * @param float|null  $latencyMs
     * @param int|null    $statusCode
     * @param string|null $error
     */
    public function __construct(
        public private(set) bool $reachable {
            /**
             * @return bool
             */ get => $this->reachable;
        },
        #[SerializedName('latency_ms')]
        public private(set) ?float $latencyMs {
            /**
             * @return float|null
             */ get => $this->latencyMs;
        },
        #[SerializedName('status_code')]
        public private(set) ?int $statusCode {
            /**
             * @return int|null
             */ get => $this->statusCode;
        },
        public private(set) ?string $error {
            /**
             * @return string|null
             */ get => $this->error;
        },
    ) {
    }

    /**
     * O mesmo status com outra mensagem de erro (o UseCase troca
     * os segredos da mensagem por "***").
     *
     * @param string|null $error
     *
     * @return self
     */
    public function withError(?string $error): self {
        return new self(
            reachable: $this->reachable,
            latencyMs: $this->latencyMs,
            statusCode: $this->statusCode,
            error: $error,
        );
    }
}
