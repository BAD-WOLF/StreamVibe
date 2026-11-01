<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Shared\OpenApi;

/**
 * Schemas (JSON Schema) dos parâmetros que se repetem nas operações do
 * API Platform: locale do prefixo da rota, ids do TMDB e de usuário,
 * página, idioma...
 *
 *     new Parameter(
 *         name: 'language',
 *         in: 'query',
 *         description: ParameterSchema::LANGUAGE_DESCRIPTION,
 *         schema: ParameterSchema::LANGUAGE,
 *     )
 *
 * São constantes (e não métodos) porque argumentos de atributo PHP só
 * aceitam expressões constantes. As regras espelham as constraints dos
 * Request DTOs (Application) e os requirements das rotas — se uma mudar,
 * a outra muda junto. Os textos ficam em inglês, como o resto da
 * documentação do Swagger.
 *
 * NOTA (out/2026): criado na reescrita das EntryPoints. Antes cada
 * operação repetia o próprio array do `_locale` (com default "pt_BR" mas
 * sem a lista de locales aceitos), e nenhuma documentava `language` ou os
 * limites de `page`.
 */
final class ParameterSchema {
    /**
     * Locales aceitos no prefixo /{_locale}/api (mesma lista do requirement
     * em config/routes/api_platform.yaml e config/routes.yaml).
     */
    public const array LOCALE = [
        'type' => 'string',
        'enum' => ['pt_BR', 'en', 'es'],
        'default' => 'pt_BR',
    ];

    public const string LOCALE_DESCRIPTION = 'API locale (route prefix): language of the API messages.';

    /**
     * Ids do TMDB (filme, pessoa): mesmo Range dos Request DTOs.
     */
    public const array TMDB_ID = [
        'type' => 'integer',
        'minimum' => 1,
        'maximum' => 999999999,
    ];

    /**
     * Id de usuário da aplicação (verify/email/status/{userId},
     * reset-password/requests/{userId} e o `id` do link de verificação):
     * mesmo Positive dos Request DTOs de autenticação.
     */
    public const array USER_ID = [
        'type' => 'integer',
        'minimum' => 1,
    ];

    /**
     * Página das listas do TMDB (o TMDB não aceita página acima de 500).
     */
    public const array PAGE = [
        'type' => 'integer',
        'minimum' => 1,
        'maximum' => 500,
        'default' => 1,
    ];

    public const string PAGE_DESCRIPTION = 'Result page (1 to 500).';

    /**
     * Idioma do conteúdo devolvido pelo TMDB (constraint LanguageCode).
     */
    public const array LANGUAGE = [
        'type' => 'string',
        'pattern' => '^[a-z]{2}(-[A-Z]{2})?$',
        'example' => 'pt-BR',
    ];

    public const string LANGUAGE_DESCRIPTION = 'Language of the TMDB content (ISO 639-1, optionally with an ISO 3166-1 region: "pt", "pt-BR", "en-US"). Without it TMDB answers in English.';
}
