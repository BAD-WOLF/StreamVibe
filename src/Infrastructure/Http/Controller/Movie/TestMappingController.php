<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Movie;

use App\Application\Movie\DTO\SearchMoviesResponse;
use App\Infrastructure\ApiPlatform\Shared\Mapper\OutputMapperRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Test controller to demonstrate the output mapping system
 *
 * This controller shows how the mapping system works by creating a sample
 * DTO and automatically mapping it to an ApiPlatform output model.
 */
#[AsController]
class TestMappingController extends AbstractController
{
    public function __construct(
        private OutputMapperRegistry $outputMapperRegistry,
    ) {}

    /**
     * Test endpoint to demonstrate automatic DTO mapping
     */
    #[Route('/test/mapping', name: 'test_mapping', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        // Create a sample DTO response
        $sampleMovies = [
            [
                'id' => 550,
                'title' => 'Fight Club',
                'overview' => 'An insomniac office worker...',
                'release_date' => '1999-10-15',
                'vote_average' => 8.4,
                'poster_path' => '/pB8BM7pdSp6B6Ih7QZ4DrQ3PmJK.jpg'
            ],
            [
                'id' => 155,
                'title' => 'The Dark Knight',
                'overview' => 'Batman raises the stakes...',
                'release_date' => '2008-07-18',
                'vote_average' => 8.5,
                'poster_path' => '/qJ2tW6WMUDux911r6m7haRef0WH.jpg'
            ]
        ];

        // Create DTO using the Application layer response
        $dto = SearchMoviesResponse::success(
            movies: $sampleMovies,
            page: 1,
            totalPages: 1,
            totalResults: 2,
            message: 'Test movies retrieved successfully'
        );

        // Show the original DTO structure
        $originalStructure = $dto->toArray();

        try {
            // Automatically map DTO to ApiPlatform output model
            $mappedOutput = $this->outputMapperRegistry->map($dto);

            return $this->json([
                'mapping_success' => true,
                'message' => 'DTO successfully mapped to ApiPlatform output',
                'original_dto_structure' => $originalStructure,
                'mapped_output_structure' => method_exists($mappedOutput, 'toArray')
                    ? $mappedOutput->toArray()
                    : $mappedOutput,
                'mapper_stats' => $this->outputMapperRegistry->getStats(),
                'demo_explanation' => [
                    'what_happened' => 'The SearchMoviesResponse DTO was automatically mapped to SearchMoviesOutput',
                    'benefit' => 'When you change the DTO structure, the API output changes automatically',
                    'no_manual_sync' => 'No need to manually update both DTO and ApiPlatform model',
                    'maintains_architecture' => 'DDD and Clean Architecture principles are preserved'
                ]
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'mapping_success' => false,
                'error' => $e->getMessage(),
                'original_dto_structure' => $originalStructure,
                'available_mappers' => $this->outputMapperRegistry->getRegisteredDtoClasses(),
                'help' => [
                    'problem' => 'No mapper found for SearchMoviesResponse',
                    'solution' => 'Make sure SearchMoviesOutputMapper is registered with app.output_mapper tag',
                    'check_services_yaml' => 'Verify the mapper is tagged in config/services.yaml'
                ]
            ], 500);
        }
    }
}
