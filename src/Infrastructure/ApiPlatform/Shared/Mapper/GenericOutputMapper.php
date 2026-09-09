<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Shared\Mapper;

use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use RuntimeException;

/**
 * Generic mapper that automatically maps DTOs to Output Models using reflection
 *
 * This mapper eliminates the need to create individual mappers for each DTO.
 * It uses reflection to automatically map properties between DTOs and Output Models
 * based on the AutoMapFromDto attribute configuration.
 */
final class GenericOutputMapper implements OutputMapperInterface
{
    public function __construct(
        private string $outputClass,
        private string $dtoClass,
        private array $fieldMap = [],
        private array $excludeFields = [],
        private string $strategy = 'auto',
    ) {}

    /**
     * {@inheritDoc}
     */
    public function map(object $dto): object
    {
        if (!$dto instanceof $this->dtoClass) {
            throw new InvalidArgumentException(
                sprintf(
                    "Expected DTO of type %s, got %s",
                    $this->dtoClass,
                    get_class($dto),
                ),
            );
        }

        try {
            return $this->performMapping($dto);
        } catch (ReflectionException $e) {
            throw new RuntimeException(
                sprintf(
                    "Failed to map %s to %s: %s",
                    $this->dtoClass,
                    $this->outputClass,
                    $e->getMessage(),
                ),
                0,
                $e,
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDtoClass(): string
    {
        return $this->dtoClass;
    }

    /**
     * {@inheritDoc}
     */
    public function getOutputClass(): string
    {
        return $this->outputClass;
    }

    /**
     * Creates a GenericOutputMapper from an Output Model class with AutoMapFromDto attribute
     */
    public static function fromOutputClass(string $outputClass): self
    {
        try {
            $reflection = new ReflectionClass($outputClass);
            $attributes = $reflection->getAttributes(AutoMapFromDto::class);

            if (empty($attributes)) {
                throw new InvalidArgumentException(
                    sprintf(
                        "Output class %s must have #[AutoMapFromDto] attribute",
                        $outputClass,
                    ),
                );
            }

            $attribute = $attributes[0]->newInstance();

            return new self(
                outputClass: $outputClass,
                dtoClass: $attribute->dtoClass,
                fieldMap: $attribute->fieldMap,
                excludeFields: $attribute->excludeFields,
                strategy: $attribute->strategy,
            );
        } catch (ReflectionException $e) {
            throw new RuntimeException(
                sprintf("Failed to create mapper for %s: %s", $outputClass, $e->getMessage()),
                0,
                $e,
            );
        }
    }

    /**
     * Performs the actual mapping using reflection
     */
    private function performMapping(object $dto): object
    {
        $outputReflection = new ReflectionClass($this->outputClass);
        $dtoReflection = new ReflectionClass($dto);

        // Get constructor parameters for the output class
        $constructor = $outputReflection->getConstructor();
        if (!$constructor) {
            throw new RuntimeException(
                sprintf("Output class %s has no constructor", $this->outputClass),
            );
        }

        $constructorParams = $constructor->getParameters();
        $args = [];

        foreach ($constructorParams as $param) {
            $paramName = $param->getName();

            // Skip excluded fields
            if (in_array($paramName, $this->excludeFields, true)) {
                $args[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
                continue;
            }

            // NOTA (bug corrigido, ago/2026): quando o parâmetro do
            // construtor é tipado exatamente como a própria classe do DTO
            // de origem — ex.: `public GetPersonDetailsResponseDto $data`
            // em PersonDetailsOutput, apontando pro próprio
            // GetPersonDetailsResponse que está sendo mapeado — o valor
            // correto é o DTO inteiro, não um sub-campo extraído dele.
            // Sem essa checagem, o fluxo caía na Estratégia 3 de
            // extractValueFromDto() para o campo 'data', que faz
            // $dto->toArray()['data'] (devolve um ARRAY) e tenta passar
            // isso pro construtor onde é exigido um OBJETO — TypeError
            // sempre, para qualquer instância mapeada por essa classe.
            // Verificar por TIPO (não por nome de parâmetro) deixa a
            // correção genérica para qualquer Output com esse desenho.
            $paramType = $param->getType();
            if (
                $paramType instanceof ReflectionNamedType
                && !$paramType->isBuiltin()
                && ltrim($paramType->getName(), '\\') === ltrim($this->dtoClass, '\\')
            ) {
                $args[] = $dto;
                continue;
            }

            // Use custom field mapping if provided
            $dtoField = $this->fieldMap[$paramName] ?? $paramName;

            $value = $this->extractValueFromDto($dto, $dtoField, $dtoReflection);

            // If value is null and parameter has default, use default
            if ($value === null && $param->isDefaultValueAvailable()) {
                $value = $param->getDefaultValue();
            }

            $args[] = $value;
        }

        return $outputReflection->newInstanceArgs($args);
    }

    /**
     * Extracts a value from the DTO using various strategies
     */
    private function extractValueFromDto(object $dto, string $fieldName, ReflectionClass $dtoReflection): mixed
    {
        // Strategy 1: Try direct property access (public properties)
        if ($dtoReflection->hasProperty($fieldName)) {
            $property = $dtoReflection->getProperty($fieldName);
            if ($property->isPublic()) {
                return $dto->$fieldName;
            }
        }

        // Strategy 2: Try getter methods
        $getterMethods = [
            'get' . ucfirst($fieldName),
            'is' . ucfirst($fieldName),
            'has' . ucfirst($fieldName),
            $fieldName, // Direct method name
        ];

        foreach ($getterMethods as $method) {
            if ($dtoReflection->hasMethod($method)) {
                $methodReflection = $dtoReflection->getMethod($method);
                if ($methodReflection->isPublic() && $methodReflection->getNumberOfRequiredParameters() === 0) {
                    return $dto->$method();
                }
            }
        }

        // Strategy 3: Special handling for common patterns
        switch ($fieldName) {
            case 'data':
                // Try toArray() method first, then data property
                if ($dtoReflection->hasMethod('toArray')) {
                    $arrayData = $dto->toArray();

                    return $arrayData['data'] ?? $arrayData;
                }
                break;

            case 'success':
                // Try isSuccess() or getSuccess()
                if ($dtoReflection->hasMethod('isSuccess')) {
                    return $dto->isSuccess();
                }
                break;

            case 'message':
                // Try getMessage()
                if ($dtoReflection->hasMethod('getMessage')) {
                    return $dto->getMessage();
                }
                break;

            case 'errors':
                // Try getErrors() or hasErrors()
                if ($dtoReflection->hasMethod('getErrors')) {
                    return $dto->getErrors();
                } elseif ($dtoReflection->hasMethod('hasErrors')) {
                    return $dto->hasErrors() ? ['error' => 'Unknown error'] : [];
                }
                break;
        }

        // Strategy 4: If strict mode, throw exception
        if ($this->strategy === 'strict') {
            throw new RuntimeException(
                sprintf(
                    "Cannot extract field '%s' from DTO %s in strict mode",
                    $fieldName,
                    $this->dtoClass,
                ),
            );
        }

        // Strategy 5: Return null for loose/auto mode
        return null;
    }

    /**
     * Gets the AutoMapFromDto attribute from an output class
     */
    public static function getAutoMapAttribute(string $outputClass): ?AutoMapFromDto
    {
        try {
            $reflection = new ReflectionClass($outputClass);
            $attributes = $reflection->getAttributes(AutoMapFromDto::class);

            if (empty($attributes)) {
                return null;
            }

            return $attributes[0]->newInstance();
        } catch (ReflectionException) {
            return null;
        }
    }

    /**
     * Checks if an output class has the AutoMapFromDto attribute
     */
    public static function hasAutoMapAttribute(string $outputClass): bool
    {
        return self::getAutoMapAttribute($outputClass) !== null;
    }
}

