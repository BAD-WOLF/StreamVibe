<?php

declare(strict_types = 1);

namespace App\Application\Image\Shared;

/**
 * Catálogo único dos tamanhos de imagem do TMDB que a API aceita.
 *
 * - BY_TYPE: os tamanhos por tipo de imagem, como o /configuration do
 *   TMDB devolve em images.*_sizes (é o que GET /images/sizes mostra).
 * - ALL: a união de BY_TYPE, sem repetição — o que GetImageRequest e
 *   GetImageInfoRequest validam.
 * - ROUTE_REQUIREMENT: ALL no formato de requirement de rota, para o
 *   {size} de /image/{size}/{endpoint}.
 *
 * ALL e ROUTE_REQUIREMENT são escritos por extenso porque constante de
 * classe (e argumento de atributo) não aceita chamada de função; o
 * ImageSizeTest confere que os três continuam batendo.
 *
 * NOTA (out/2026): antes havia três listas diferentes — a da rota e a do
 * GetImageRequest (sem w45 e w300) e a do GET /images/sizes (com w45 e
 * w300). O endpoint de tamanhos anunciava w45/w300 e a rota de imagem
 * respondia 404 para eles. Agora as três saem daqui.
 */
final class ImageSize {
    public const string ORIGINAL = 'original';

    /**
     * Tamanhos suportados por tipo de imagem.
     */
    public const array BY_TYPE = [
        'poster' => ['w92', 'w154', 'w185', 'w342', 'w500', 'w780', 'original'],
        'backdrop' => ['w300', 'w780', 'w1280', 'original'],
        'logo' => ['w45', 'w92', 'w154', 'w185', 'w300', 'w500', 'original'],
        'profile' => ['w45', 'w185', 'h632', 'original'],
        'still' => ['w92', 'w185', 'w300', 'original'],
    ];

    /**
     * Todos os tamanhos aceitos (união de BY_TYPE).
     */
    public const array ALL = [
        'w45',
        'w92',
        'w154',
        'w185',
        'w300',
        'w342',
        'w500',
        'w780',
        'w1280',
        'h632',
        'original',
    ];

    /**
     * ALL como requirement de rota.
     */
    public const string ROUTE_REQUIREMENT = 'w45|w92|w154|w185|w300|w342|w500|w780|w1280|h632|original';
}
