<?php

declare(strict_types = 1);

namespace App\Application\Shared\Port;

/**
 * Porta para esconder segredos num texto antes de ele sair pela API: os
 * valores dos segredos da aplicação (APP_SECRET, TMDB_TOKEN, a senha do
 * banco...) e o que tem cara de segredo (senha numa URL, JWT, "Bearer ...",
 * password=...). Cada trecho escondido vira "***".
 *
 * Quem usa é quem mostra texto que a aplicação não escreveu: o painel do
 * sistema (linha de comando dos processos, linhas dos logs, mensagens de
 * erro do banco e do TMDB). A implementação
 * (Infrastructure\Security\SecretMasker) recebe os valores pelo container.
 *
 * NOTA (nov/2026): criada com o painel do sistema (ROLE_DEV).
 */
interface SecretMaskerInterface {
    /**
     * O texto com os segredos trocados por "***".
     *
     * @param string $text
     *
     * @return string
     */
    public function mask(string $text): string;
}
