<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Dev;

use App\Application\Movie\DTO\Search\Response\SearchMoviesResponse;
use App\Application\Movie\Shared\MovieListItemMapper;
use App\Infrastructure\ApiPlatform\Shared\Mapper\OutputMapperRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Diagnóstico do auto-mapping, só no ambiente dev:
 *
 *     GET /test/mapping
 *
 * - registered_mappings: cada DTO de resposta e o Output que o
 *   OutputMapperRegistry usa para ele (o que o OutputMapperDiscoveryPass
 *   encontrou);
 * - sample: uma busca de filmes de exemplo (sem chamar o TMDB) mapeada
 *   pelo registry — exatamente o formato que GET /movies/search devolve.
 *
 * NOTA (out/2026):
 * - Era Controller/Movie/TestMappingController: rota pública em
 *   produção, que expunha as classes internas do mapper. Agora a rota
 *   (env: 'dev') e o serviço (#[When('dev')]) só existem em dev; em prod
 *   e em test, /test/mapping é 404.
 * - Usava o SearchMoviesResponse antigo (Application\Movie\DTO, com
 *   success(movies: array de arrays) e toArray()), que não existe mais —
 *   em dev a rota respondia 500. Agora monta o DTO atual pelo
 *   MovieListItemMapper, como o SearchMoviesUseCase.
 * - Saíram o try/catch (um erro de mapeamento é justamente o que este
 *   diagnóstico deve mostrar, e em dev o Symfony exibe a página de erro
 *   com o stack trace) e o texto explicativo dentro do JSON (está aqui).
 */
#[When(env: 'dev')]
#[AsController]
final class TestMappingController extends AbstractController
{
    /**
     * Dois filmes no formato do TMDB, só para o exemplo.
     */
    private const array SAMPLE_TMDB_MOVIES = [
        [
            'id' => 550,
            'title' => 'Fight Club',
            'original_title' => 'Fight Club',
            'overview' => 'An insomniac office worker...',
            'release_date' => '1999-10-15',
            'poster_path' => '/pB8BM7pdSp6B6Ih7QZ4DrQ3PmJK.jpg',
            'vote_average' => 8.4,
            'genre_ids' => [18],
            'original_language' => 'en',
        ],
        [
            'id' => 155,
            'title' => 'The Dark Knight',
            'original_title' => 'The Dark Knight',
            'overview' => 'Batman raises the stakes...',
            'release_date' => '2008-07-18',
            'poster_path' => '/qJ2tW6WMUDux911r6m7haRef0WH.jpg',
            'vote_average' => 8.5,
            'genre_ids' => [18, 28, 80, 53],
            'original_language' => 'en',
        ],
    ];

    /**
     * @param \App\Infrastructure\ApiPlatform\Shared\Mapper\OutputMapperRegistry $outputMapperRegistry
     * @param \App\Application\Movie\Shared\MovieListItemMapper                 $movieListItemMapper
     */
    public function __construct(
        private readonly OutputMapperRegistry $outputMapperRegistry,
        private readonly MovieListItemMapper $movieListItemMapper,
    ) {}

    #[Route('/test/mapping', name: 'test_mapping', methods: ['GET'], env: 'dev')]
    public function __invoke(): JsonResponse
    {
        $mappings = [];
        foreach ($this->outputMapperRegistry->getAllMappers() as $dtoClass => $mapper) {
            $mappings[$dtoClass] = $mapper->getOutputClass();
        }
        ksort($mappings);

        $sample = SearchMoviesResponse::success(
            movies: $this->movieListItemMapper->mapList(self::SAMPLE_TMDB_MOVIES),
            page: 1,
            totalPages: 1,
            totalResults: count(self::SAMPLE_TMDB_MOVIES),
            message: 'Sample search mapped by the OutputMapperRegistry',
        );

        return $this->json([
            'registered_mappings' => $mappings,
            'total_mappings' => count($mappings),
            'sample' => [
                'dto' => SearchMoviesResponse::class,
                'output' => $this->outputMapperRegistry->getOutputClass(SearchMoviesResponse::class),
                'json' => $this->outputMapperRegistry->map($sample),
            ],
        ]);
    }
}
