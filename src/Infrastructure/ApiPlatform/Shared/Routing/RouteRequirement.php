<?php

declare(strict_types = 1);

namespace App\Infrastructure\ApiPlatform\Shared\Routing;

/**
 * Requirements (regex) de parâmetros de rota usados nas EntryPoints:
 *
 *     requirements: ['movieId' => RouteRequirement::INTEGER]
 *
 * São constantes porque argumentos de atributo PHP só aceitam expressões
 * constantes.
 *
 * NOTA (out/2026): criado junto com a reescrita das EntryPoints.
 * - INTEGER: as rotas usavam '\d+', e um id com 20 dígitos casava com a
 *   rota mas não cabia no `int $movieId` do controller: o Symfony passa o
 *   atributo da rota como string, o PHP não consegue convertê-la para int
 *   e lança TypeError — 500 com "internal_error". Com no máximo 18
 *   dígitos o valor sempre cabe em int (PHP_INT_MAX tem 19), então
 *   números fora do intervalo de negócio chegam ao UseCase e viram 400 de
 *   validação, e números absurdos não casam com a rota (404).
 * - PATH_SEGMENT: as rotas de imagem usavam '.+' no {endpoint}, que
 *   aceita barras. Com /image/{size}/{endpoint} e /image/{endpoint} em
 *   EntryPoints separadas, "/image/w500/abc.jpg" casaria com as duas, e
 *   qual atenderia dependeria da ordem de carga das classes. O arquivo do
 *   TMDB é sempre um nome só ("abc.jpg"), então o {endpoint} não precisa
 *   (e não deve) aceitar barra.
 */
final class RouteRequirement {
    /**
     * Inteiro não negativo que cabe em int (ids e página).
     */
    public const string INTEGER = '\d{1,18}';

    /**
     * Um segmento de path, sem barra (nome de arquivo).
     */
    public const string PATH_SEGMENT = '[^/]+';
}
