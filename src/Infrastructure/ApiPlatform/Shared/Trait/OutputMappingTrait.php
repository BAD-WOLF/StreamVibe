<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Shared\Trait;

use App\Infrastructure\ApiPlatform\Shared\Mapper\OutputMapperRegistry;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Trait for automatic DTO to ApiPlatform output mapping in controllers
 *
 * This trait provides methods to automatically convert Application layer DTOs
 * to ApiPlatform output models and return them as JSON responses.
 * It eliminates the need to manually maintain separate DTO and output structures.
 *
 * NOTA (out/2026): o auto-mapping nunca tinha rodado em produção. O
 * setOutputMapperRegistry() não tinha #[Required] e nada no services.yaml o
 * chamava, então $outputMapperRegistry ficava sempre null e o
 * safeMapToJsonResponse() caía no fallback $dto->toArray() — o JSON real
 * era o toArray(), e os Output models só alimentavam o Swagger (que por
 * isso documentava formatos diferentes do real). Agora:
 * - #[Required] faz o autowiring injetar o registry em todo controller que
 *   usa este trait;
 * - os controllers chamam mapToJsonResponse(), que é estrito: DTO sem
 *   mapper registrado é erro (500 e log), não um JSON alternativo
 *   silencioso;
 * - safeMapToJsonResponse() saiu, junto com o fallback para toArray()
 *   (que também saiu dos DTOs de resposta). O JSON real e o Swagger saem
 *   do mesmo Output model.
 */
trait OutputMappingTrait
{
    private ?OutputMapperRegistry $outputMapperRegistry = null;

    /**
     * Sets the output mapper registry (autowired via #[Required])
     *
     * @param OutputMapperRegistry $registry
     */
    #[Required]
    public function setOutputMapperRegistry(OutputMapperRegistry $registry): void
    {
        $this->outputMapperRegistry = $registry;
    }

    /**
     * Maps a DTO to an output model and returns a JSON response
     *
     * @param object $dto The DTO to map and return
     * @param int $status HTTP status code (default: 200)
     * @param array $headers Additional HTTP headers
     * @param array $context Serialization context
     *
     * @return JsonResponse The JSON response with mapped output
     * @throws RuntimeException If no mapper registry is available or DTO cannot be mapped
     */
    protected function mapToJsonResponse(
        object $dto,
        int $status = 200,
        array $headers = [],
        array $context = []
    ): JsonResponse {
        $this->ensureMapperRegistryAvailable();

        if (!$this->outputMapperRegistry->canMap($dto)) {
            throw new RuntimeException(
                sprintf(
                    'Cannot map DTO of type %s. No mapper registered.',
                    get_class($dto)
                )
            );
        }

        $mappedOutput = $this->outputMapperRegistry->map($dto);

        return $this->json($mappedOutput, $status, $headers, $context);
    }

    /**
     * Maps a DTO to an output model
     *
     * @param object $dto The DTO to map
     *
     * @return object The mapped output model
     * @throws RuntimeException If no mapper registry is available or DTO cannot be mapped
     */
    protected function mapDto(object $dto): object
    {
        $this->ensureMapperRegistryAvailable();

        return $this->outputMapperRegistry->map($dto);
    }

    /**
     * Checks if a DTO can be mapped
     *
     * @param object $dto The DTO to check
     *
     * @return bool True if the DTO can be mapped, false otherwise
     */
    protected function canMapDto(object $dto): bool
    {
        if ($this->outputMapperRegistry === null) {
            return false;
        }

        return $this->outputMapperRegistry->canMap($dto);
    }

    /**
     * Gets the output class for a given DTO class
     *
     * @param class-string $dtoClass The DTO class
     *
     * @return class-string The corresponding output class
     * @throws RuntimeException If no mapper registry is available or no mapping exists
     */
    protected function getOutputClass(string $dtoClass): string
    {
        $this->ensureMapperRegistryAvailable();

        return $this->outputMapperRegistry->getOutputClass($dtoClass);
    }

    /**
     * Ensures that the mapper registry is available
     *
     * @throws RuntimeException If no mapper registry is set
     */
    private function ensureMapperRegistryAvailable(): void
    {
        if ($this->outputMapperRegistry === null) {
            throw new RuntimeException(
                'OutputMapperRegistry is not available. Make sure it is injected into the controller.'
            );
        }
    }

    /**
     * Helper method to create a JSON response with proper error handling for DTO mapping
     *
     * This method combines UseCase execution with automatic DTO mapping in a single call.
     *
     * @param callable $useCaseExecutor A callable that returns a DTO
     * @param int $successStatus HTTP status code for success (default: 200)
     * @param array $headers Additional HTTP headers
     * @param array $context Serialization context
     *
     * @return JsonResponse The JSON response
     */
    protected function executeAndMap(
        callable $useCaseExecutor,
        int $successStatus = 200,
        array $headers = [],
        array $context = []
    ): JsonResponse {
        $result = $useCaseExecutor();

        return $this->mapToJsonResponse($result, $successStatus, $headers, $context);
    }
}
