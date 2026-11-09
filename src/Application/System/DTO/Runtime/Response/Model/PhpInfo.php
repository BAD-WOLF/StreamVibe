<?php

declare(strict_types = 1);

namespace App\Application\System\DTO\Runtime\Response\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * O PHP que atende a API (`data.php` de GET /system/runtime):
 *
 *     {"version": "8.4.14", "sapi": "fpm-fcgi", "ini_file": "/usr/local/etc/php/php.ini",
 *      "memory_limit_bytes": 134217728, "max_execution_time_seconds": 30,
 *      "upload_max_filesize_bytes": 2097152, "post_max_size_bytes": 8388608,
 *      "timezone": "UTC", "extensions": ["Core", "ctype", "curl", ...]}
 *
 * - os limites têm o nome da diretiva do php.ini e vêm em bytes ou
 *   segundos; null = sem limite (memory_limit -1, max_execution_time 0,
 *   post_max_size 0);
 * - ini_file: o php.ini carregado; null quando o PHP roda sem um (só com os
 *   valores padrão);
 * - extensions: as carregadas, em ordem alfabética.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final class PhpInfo {
    /**
     * @param string       $version
     * @param string       $sapi
     * @param string|null  $iniFile
     * @param int|null     $memoryLimitBytes
     * @param int|null     $maxExecutionTimeSeconds
     * @param int|null     $uploadMaxFilesizeBytes
     * @param int|null     $postMaxSizeBytes
     * @param string       $timezone
     * @param list<string> $extensions
     */
    public function __construct(
        public private(set) string $version {
            /**
             * @return string
             */ get => $this->version;
        },
        public private(set) string $sapi {
            /**
             * @return string
             */ get => $this->sapi;
        },
        #[SerializedName('ini_file')]
        public private(set) ?string $iniFile {
            /**
             * @return string|null
             */ get => $this->iniFile;
        },
        #[SerializedName('memory_limit_bytes')]
        public private(set) ?int $memoryLimitBytes {
            /**
             * @return int|null
             */ get => $this->memoryLimitBytes;
        },
        #[SerializedName('max_execution_time_seconds')]
        public private(set) ?int $maxExecutionTimeSeconds {
            /**
             * @return int|null
             */ get => $this->maxExecutionTimeSeconds;
        },
        #[SerializedName('upload_max_filesize_bytes')]
        public private(set) ?int $uploadMaxFilesizeBytes {
            /**
             * @return int|null
             */ get => $this->uploadMaxFilesizeBytes;
        },
        #[SerializedName('post_max_size_bytes')]
        public private(set) ?int $postMaxSizeBytes {
            /**
             * @return int|null
             */ get => $this->postMaxSizeBytes;
        },
        public private(set) string $timezone {
            /**
             * @return string
             */ get => $this->timezone;
        },
        public private(set) array $extensions {
            /**
             * @return list<string>
             */ get => $this->extensions;
        },
    ) {
    }
}
