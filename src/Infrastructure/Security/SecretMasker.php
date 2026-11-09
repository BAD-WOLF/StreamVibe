<?php

declare(strict_types = 1);

namespace App\Infrastructure\Security;

use App\Application\Shared\Port\SecretMaskerInterface;

/**
 * Implementação da SecretMaskerInterface: troca por "***" os segredos de
 * um texto, de dois jeitos.
 *
 * 1. Pelo valor: os segredos da aplicação chegam pelo container
 *    (config/services.yaml, $secrets: APP_SECRET, DATABASE_URL, TMDB_TOKEN...,
 *    resolvidos como o resto da aplicação os resolve — .env ou cofre). De
 *    um DSN (scheme://usuário:senha@host) vale só a senha (ou o usuário,
 *    quando não há senha, como no DSN do Sentry): o host e o banco continuam
 *    visíveis, porque ajudam a diagnosticar. Cada valor também é procurado
 *    como aparece num JSON e numa URL (escapado). Valores com menos de
 *    MIN_SECRET_LENGTH caracteres ficam de fora (seriam achados em texto
 *    comum).
 * 2. Pela forma (PATTERNS): chave privada PEM, senha numa URL, header
 *    Authorization (Bearer/Basic), JWT, e chave=valor ou "chave": "valor"
 *    quando a chave fala em password, passphrase, secret, token ou api key.
 *    Pega segredos que não são da aplicação (o token de um usuário num log,
 *    a senha de outro serviço).
 *
 * NOTA (nov/2026): criado com o painel do sistema (ROLE_DEV): processos,
 * logs e mensagens de erro saem por ele. Os segredos nunca vão para o JSON
 * da API — nem para quem tem ROLE_DEV.
 */
final readonly class SecretMasker implements SecretMaskerInterface {
    public const string MASK = '***';

    private const int MIN_SECRET_LENGTH = 6;

    /**
     * Padrão => troca, na ordem: o PEM primeiro (um bloco de várias linhas
     * vira uma linha só), depois o que vem dentro de URLs e headers, e por
     * fim os pares chave/valor.
     */
    private const array PATTERNS = [
        '/-----BEGIN ((?:[A-Z0-9]+ )*PRIVATE KEY)-----.*?-----END \1-----/s' => '-----BEGIN $1-----***-----END $1-----',
        '~([a-z][a-z0-9+.-]*://[^:/@\s"\'<>]*):[^@/\s"\'<>]+@~i' => '$1:***@',
        '/\b(Bearer|Basic)\s+[A-Za-z0-9._~+\/=-]+/i' => '$1 ***',
        '/\beyJ[A-Za-z0-9_-]{5,}\.[A-Za-z0-9_-]{5,}\.[A-Za-z0-9_-]+/' => '***',
        '/("[^"]*(?:password|passwd|passphrase|secret|token|api[_-]?key)[^"]*"\s*:\s*)"(?:[^"\\\\]|\\\\.)*"/i' => '$1"***"',
        '/\b([\w.-]*(?:password|passwd|passphrase|secret|token|api[_-]?key)[\w.-]*=)[^\s&"\',;]+/i' => '$1***',
    ];

    /**
     * Os valores a esconder, do mais longo para o mais curto (um valor que
     * contém outro é trocado inteiro).
     *
     * @var list<string>
     */
    private array $values;

    /**
     * @param array<string, mixed> $secrets nome => valor dos segredos da aplicação (null = não definido)
     */
    public function __construct(array $secrets) {
        $values = [];

        foreach ($secrets as $secret) {
            if (!is_string($secret)) {
                continue;
            }

            foreach (self::sensitivePartsOf($secret) as $part) {
                foreach (self::variantsOf($part) as $variant) {
                    if (strlen($variant) >= self::MIN_SECRET_LENGTH) {
                        $values[$variant] = strlen($variant);
                    }
                }
            }
        }

        arsort($values);
        $this->values = array_map('strval', array_keys($values));
    }

    /**
     * {@inheritDoc}
     */
    public function mask(string $text): string {
        if ($text === '') {
            return $text;
        }

        if ($this->values !== []) {
            $text = str_replace($this->values, self::MASK, $text);
        }

        foreach (self::PATTERNS as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text) ?? $text;
        }

        return $text;
    }

    /**
     * O que esconder de um segredo: de um DSN com credenciais, a senha (ou,
     * sem senha, o usuário); de qualquer outro valor, ele inteiro.
     *
     * @param string $secret
     *
     * @return list<string>
     */
    private static function sensitivePartsOf(string $secret): array {
        if (preg_match('~^[a-z][a-z0-9+.-]*://~i', $secret) === 1) {
            $url = parse_url($secret);

            if (is_array($url) && isset($url['pass']) && $url['pass'] !== '') {
                return array_values(array_unique([$url['pass'], rawurldecode($url['pass'])]));
            }

            if (is_array($url) && isset($url['user']) && $url['user'] !== '') {
                return array_values(array_unique([$url['user'], rawurldecode($url['user'])]));
            }

            return [];
        }

        return [$secret];
    }

    /**
     * O valor como ele aparece num texto: cru, numa URL (rawurlencode) e
     * dentro de uma string JSON (com e sem a barra escapada).
     *
     * @param string $value
     *
     * @return list<string>
     */
    private static function variantsOf(string $value): array {
        $variants = [$value, rawurlencode($value)];

        foreach ([JSON_UNESCAPED_UNICODE, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES] as $flags) {
            $json = json_encode($value, $flags | JSON_INVALID_UTF8_SUBSTITUTE);

            if (is_string($json)) {
                $variants[] = substr($json, 1, -1);
            }
        }

        return array_values(array_unique($variants));
    }
}
