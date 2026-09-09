<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Shared\Mapper;

/**
 * Interface for mapping Application layer DTOs to ApiPlatform output models
 *
 * This interface ensures consistency in how we convert Use Case responses
 * to ApiPlatform models, maintaining separation of concerns while avoiding
 * duplication of data structures.
 *
 * @template TDto
 * @template TOutput
 */
interface OutputMapperInterface
{
    /**
     * Maps an Application layer DTO to an ApiPlatform output model
     *
     * @param TDto $dto The DTO from the Application layer
     *
     * @return TOutput The mapped ApiPlatform output model
     */
    public function map(object $dto): object;

    /**
     * Returns the DTO class name that this mapper handles
     *
     * @return class-string<TDto>
     */
    public function getDtoClass(): string;

    /**
     * Returns the output model class name that this mapper produces
     *
     * @return class-string<TOutput>
     */
    public function getOutputClass(): string;
}
