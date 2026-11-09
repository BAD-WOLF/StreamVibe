<?php

declare(strict_types = 1);

namespace App\Application\System\DTO\Runtime\Response\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * O Symfony (`data.symfony` de GET /system/runtime):
 *
 *     {"version": "7.4.16", "environment": "prod", "debug": false,
 *      "end_of_maintenance": "2028-11", "end_of_life": "2029-11",
 *      "bundles": ["ApiPlatformBundle", "DoctrineBundle", ...]}
 *
 * - end_of_maintenance: até quando (ano-mês) a versão recebe correções de
 *   bugs; end_of_life: até quando recebe correções de segurança;
 * - bundles: os habilitados neste ambiente (config/bundles.php), em ordem
 *   alfabética.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class SymfonyInfo {
    /**
     * @param string       $version
     * @param string       $environment
     * @param bool         $debug
     * @param string       $endOfMaintenance
     * @param string       $endOfLife
     * @param list<string> $bundles
     */
    public function __construct(
        public private(set) string $version {
            /**
             * @return string
             */ get => $this->version;
        },
        public private(set) string $environment {
            /**
             * @return string
             */ get => $this->environment;
        },
        public private(set) bool $debug {
            /**
             * @return bool
             */ get => $this->debug;
        },
        #[SerializedName('end_of_maintenance')]
        public private(set) string $endOfMaintenance {
            /**
             * @return string
             */ get => $this->endOfMaintenance;
        },
        #[SerializedName('end_of_life')]
        public private(set) string $endOfLife {
            /**
             * @return string
             */ get => $this->endOfLife;
        },
        public private(set) array $bundles {
            /**
             * @return list<string>
             */ get => $this->bundles;
        },
    ) {
    }
}
