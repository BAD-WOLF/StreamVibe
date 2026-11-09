<?php

declare(strict_types = 1);

namespace App\Application\System\UseCase\Dependencies;

use App\Application\Shared\Port\SecretMaskerInterface;
use App\Application\System\DTO\Dependencies\Response\GetDependenciesStatusResponse;
use App\Application\System\Port\DatabaseInspectorInterface;
use App\Application\System\Port\MovieDatabaseProbeInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * As dependências da API, conferidas na hora (GET /system/dependencies,
 * ROLE_DEV): o banco de dados e o TMDB.
 *
 * As mensagens de erro vêm do driver do banco e do HttpClient, que podem
 * citar endereços com usuário e senha: passam pelo SecretMasker antes de
 * sair.
 *
 * Não tem Request DTO: o endpoint não recebe nada.
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV).
 */
final readonly class GetDependenciesStatusUseCase {
    /**
     * @param \App\Application\System\Port\DatabaseInspectorInterface  $database
     * @param \App\Application\System\Port\MovieDatabaseProbeInterface $movieDatabase
     * @param \App\Application\Shared\Port\SecretMaskerInterface       $secretMasker
     * @param \Symfony\Contracts\Translation\TranslatorInterface       $translator
     */
    public function __construct(
        private DatabaseInspectorInterface $database,
        private MovieDatabaseProbeInterface $movieDatabase,
        private SecretMaskerInterface $secretMasker,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return \App\Application\System\DTO\Dependencies\Response\GetDependenciesStatusResponse
     */
    public function execute(): GetDependenciesStatusResponse {
        $database = $this->database->inspect();
        $tmdb = $this->movieDatabase->probe();

        return GetDependenciesStatusResponse::success(
            database: $database->error === null ? $database : $database->withError($this->secretMasker->mask($database->error)),
            tmdb: $tmdb->error === null ? $tmdb : $tmdb->withError($this->secretMasker->mask($tmdb->error)),
            message: $this->translator->trans('Dependencies checked successfully'),
        );
    }
}
