<?php

declare(strict_types = 1);

namespace App\Infrastructure\System;

use App\Application\System\DTO\Runtime\Response\Model\OpcacheInfo;
use App\Application\System\DTO\Runtime\Response\Model\PhpInfo;
use App\Application\System\DTO\Runtime\Response\Model\SymfonyInfo;
use App\Application\System\Port\RuntimeInspectorInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Implementação da RuntimeInspectorInterface: o PHP do processo que atende
 * a requisição (ini_get(), get_loaded_extensions()), o OPcache
 * (opcache_get_status(), sem a lista de scripts) e o Symfony
 * (Kernel::VERSION e as datas de suporte da versão, o ambiente, o debug e
 * os bundles do container).
 *
 * Os limites do php.ini viram bytes e segundos (ini_parse_quantity());
 * os valores que o PHP entende como "sem limite" viram null.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final readonly class PhpRuntimeInspector implements RuntimeInspectorInterface {
    /**
     * @param string                $environment %kernel.environment%
     * @param bool                  $debug       %kernel.debug%
     * @param array<string, string> $bundles     %kernel.bundles% (nome => classe)
     */
    public function __construct(
        private string $environment,
        private bool $debug,
        private array $bundles,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function php(): PhpInfo {
        $extensions = get_loaded_extensions();
        sort($extensions, SORT_NATURAL | SORT_FLAG_CASE);
        $iniFile = php_ini_loaded_file();
        $memoryLimit = self::bytes(ini_get('memory_limit'));
        $maxExecutionTime = (int) ini_get('max_execution_time');
        $postMaxSize = self::bytes(ini_get('post_max_size'));

        return new PhpInfo(
            version: PHP_VERSION,
            sapi: PHP_SAPI,
            iniFile: $iniFile === false ? null : $iniFile,
            memoryLimitBytes: $memoryLimit !== null && $memoryLimit >= 0 ? $memoryLimit : null,
            maxExecutionTimeSeconds: $maxExecutionTime > 0 ? $maxExecutionTime : null,
            uploadMaxFilesizeBytes: self::bytes(ini_get('upload_max_filesize')),
            postMaxSizeBytes: $postMaxSize !== null && $postMaxSize > 0 ? $postMaxSize : null,
            timezone: date_default_timezone_get(),
            extensions: array_values($extensions),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function opcache(): ?OpcacheInfo {
        if (!function_exists('opcache_get_status')) {
            return null;
        }

        // false com o OPcache desligado (ou com opcache.restrict_api)
        $status = @opcache_get_status(false);

        if (!is_array($status) || ($status['opcache_enabled'] ?? false) !== true) {
            return new OpcacheInfo(
                enabled: false,
                jit: false,
                memoryUsedBytes: null,
                memoryFreeBytes: null,
                memoryWastedBytes: null,
                cachedScripts: null,
                hits: null,
                misses: null,
            );
        }

        $memory = is_array($status['memory_usage'] ?? null) ? $status['memory_usage'] : [];
        $statistics = is_array($status['opcache_statistics'] ?? null) ? $status['opcache_statistics'] : [];
        $jit = is_array($status['jit'] ?? null) ? $status['jit'] : [];

        return new OpcacheInfo(
            enabled: true,
            jit: ($jit['on'] ?? false) === true,
            memoryUsedBytes: self::integer($memory['used_memory'] ?? null),
            memoryFreeBytes: self::integer($memory['free_memory'] ?? null),
            memoryWastedBytes: self::integer($memory['wasted_memory'] ?? null),
            cachedScripts: self::integer($statistics['num_cached_scripts'] ?? null),
            hits: self::integer($statistics['hits'] ?? null),
            misses: self::integer($statistics['misses'] ?? null),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function symfony(): SymfonyInfo {
        $bundles = array_map('strval', array_keys($this->bundles));
        sort($bundles, SORT_NATURAL | SORT_FLAG_CASE);

        return new SymfonyInfo(
            version: Kernel::VERSION,
            environment: $this->environment,
            debug: $this->debug,
            endOfMaintenance: self::yearMonth(Kernel::END_OF_MAINTENANCE),
            endOfLife: self::yearMonth(Kernel::END_OF_LIFE),
            bundles: array_values($bundles),
        );
    }

    /**
     * Um valor do php.ini ("128M", "-1", "2G") em bytes; null quando a
     * diretiva não existe ou está vazia.
     *
     * @param string|false $value
     *
     * @return int|null
     */
    private static function bytes(string|false $value): ?int {
        if ($value === false || trim($value) === '') {
            return null;
        }

        return @ini_parse_quantity($value);
    }

    /**
     * @param mixed $value
     *
     * @return int|null
     */
    private static function integer(mixed $value): ?int {
        return is_int($value) || is_float($value) ? (int) $value : null;
    }

    /**
     * "11/2028" (o formato do Kernel) => "2028-11".
     *
     * @param string $monthYear
     *
     * @return string
     */
    private static function yearMonth(string $monthYear): string {
        return preg_match('/^(\d{1,2})\/(\d{4})$/', $monthYear, $match) === 1
            ? sprintf('%s-%02d', $match[2], (int) $match[1])
            : $monthYear;
    }
}
