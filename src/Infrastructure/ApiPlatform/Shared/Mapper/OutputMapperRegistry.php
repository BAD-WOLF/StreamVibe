<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Shared\Mapper;

use InvalidArgumentException;
use RuntimeException;

/**
 * Registry for managing output mappers
 *
 * This registry provides a centralized way to register and retrieve mappers
 * for converting Application layer DTOs to ApiPlatform output models.
 * It enables automatic mapping without manual intervention when DTO structures change.
 */
final class OutputMapperRegistry
{
    /** @var array<class-string, OutputMapperInterface> */
    private array $mappers = [];

    /** @var array<class-string, class-string> */
    private array $dtoToOutputMap = [];

    /**
     * @param iterable<OutputMapperInterface> $mappers
     */
    public function __construct(iterable $mappers = [])
    {
        foreach ($mappers as $mapper) {
            $this->registerMapper($mapper);
        }
    }

    /**
     * Registers a mapper for a specific DTO type
     *
     * @param OutputMapperInterface $mapper The mapper to register
     *
     * @throws InvalidArgumentException If a mapper for this DTO type is already registered
     */
    public function registerMapper(OutputMapperInterface $mapper): void
    {
        $dtoClass = $mapper->getDtoClass();

        if (isset($this->mappers[$dtoClass])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Mapper for DTO class %s is already registered',
                    $dtoClass
                )
            );
        }

        $this->mappers[$dtoClass] = $mapper;
        $this->dtoToOutputMap[$dtoClass] = $mapper->getOutputClass();
    }

    /**
     * Maps a DTO to its corresponding output model
     *
     * @param object $dto The DTO to map
     *
     * @return object The mapped output model
     * @throws RuntimeException If no mapper is found for the DTO type
     */
    public function map(object $dto): object
    {
        $dtoClass = get_class($dto);
        $mapper = $this->getMapper($dtoClass);

        return $mapper->map($dto);
    }

    /**
     * Gets the mapper for a specific DTO class
     *
     * @param class-string $dtoClass The DTO class to get mapper for
     *
     * @return OutputMapperInterface The mapper instance
     * @throws RuntimeException If no mapper is found for the DTO class
     */
    public function getMapper(string $dtoClass): OutputMapperInterface
    {
        if (!isset($this->mappers[$dtoClass])) {
            throw new RuntimeException(
                sprintf(
                    'No mapper registered for DTO class %s. Available mappers: %s',
                    $dtoClass,
                    implode(', ', array_keys($this->mappers))
                )
            );
        }

        return $this->mappers[$dtoClass];
    }

    /**
     * Gets the output class for a specific DTO class
     *
     * @param class-string $dtoClass The DTO class
     *
     * @return class-string The corresponding output class
     * @throws RuntimeException If no mapper is found for the DTO class
     */
    public function getOutputClass(string $dtoClass): string
    {
        if (!isset($this->dtoToOutputMap[$dtoClass])) {
            throw new RuntimeException(
                sprintf(
                    'No output class mapping found for DTO class %s',
                    $dtoClass
                )
            );
        }

        return $this->dtoToOutputMap[$dtoClass];
    }

    /**
     * Checks if a mapper exists for the given DTO class
     *
     * @param class-string $dtoClass The DTO class to check
     *
     * @return bool True if a mapper exists, false otherwise
     */
    public function hasMapper(string $dtoClass): bool
    {
        return isset($this->mappers[$dtoClass]);
    }

    /**
     * Checks if a DTO instance can be mapped
     *
     * @param object $dto The DTO instance to check
     *
     * @return bool True if the DTO can be mapped, false otherwise
     */
    public function canMap(object $dto): bool
    {
        return $this->hasMapper(get_class($dto));
    }

    /**
     * Gets all registered DTO classes
     *
     * @return array<class-string> Array of registered DTO class names
     */
    public function getRegisteredDtoClasses(): array
    {
        return array_keys($this->mappers);
    }

    /**
     * Gets all registered mappers
     *
     * @return array<class-string, OutputMapperInterface> Array of mappers keyed by DTO class
     */
    public function getAllMappers(): array
    {
        return $this->mappers;
    }

    /**
     * Removes a mapper for a specific DTO class
     *
     * @param class-string $dtoClass The DTO class to remove mapper for
     *
     * @return bool True if mapper was removed, false if it didn't exist
     */
    public function removeMapper(string $dtoClass): bool
    {
        if (!isset($this->mappers[$dtoClass])) {
            return false;
        }

        unset($this->mappers[$dtoClass], $this->dtoToOutputMap[$dtoClass]);

        return true;
    }

    /**
     * Clears all registered mappers
     */
    public function clear(): void
    {
        $this->mappers = [];
        $this->dtoToOutputMap = [];
    }

    /**
     * Gets statistics about registered mappers
     *
     * @return array{total: int, dto_classes: array<class-string>, output_classes: array<class-string>}
     */
    public function getStats(): array
    {
        return [
            'total' => count($this->mappers),
            'dto_classes' => array_keys($this->mappers),
            'output_classes' => array_values($this->dtoToOutputMap),
        ];
    }
}
