<?php

declare(strict_types = 1);

namespace App\Application\Shared;

/**
 * Extrai o ano (4 dígitos) de uma data no formato Y-m-d ou similar.
 * Genérico de propósito — usado tanto pra data de lançamento de filme
 * quanto pra nascimento/morte de pessoa (mesma operação, domínios
 * diferentes).
 */
final class DateYearExtractor {
    /**
     * @param string|null $date
     *
     * @return int|null
     */
    public function extractYear(?string $date): ?int {
        if (empty($date)) {
            return null;
        }

        $year = substr($date, 0, 4);

        return is_numeric($year) ? (int) $year : null;
    }
}
