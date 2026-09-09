<?php

declare(strict_types = 1);

namespace App\Infrastructure\DependencyInjection;

use App\Infrastructure\ApiPlatform\Shared\Attribute\AutoMapFromDto;
use App\Infrastructure\ApiPlatform\Shared\Attribute\OutputMapper;
use App\Infrastructure\ApiPlatform\Shared\Mapper\GenericOutputMapper;
use App\Infrastructure\ApiPlatform\Shared\Mapper\OutputMapperInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;

/**
 * Compiler pass for automatic discovery of output mappers using attributes
 *
 * This pass scans the codebase for classes marked with the #[OutputMapper] attribute
 * and automatically registers them as services with the app.output_mapper tag.
 * This eliminates the need to manually register mappers in services.yaml.
 */
final class OutputMapperDiscoveryPass implements CompilerPassInterface {
    public function process(ContainerBuilder $container): void {
        // Get the project root directory
        $projectDir = $container->getParameter('kernel.project_dir');

        // Find all PHP files in mapper directories and model directories
        $finder = new Finder();
        $finder
            ->files()
            ->name('*.php')
            ->in($projectDir . '/src/Infrastructure/ApiPlatform')
            ->path('#/(Model|Mapper)/#')
            ->ignoreUnreadableDirs()
            ->exclude(['Trait', 'Interface', 'Abstract']);

        $discoveredMappers = [];

        foreach ($finder as $file) {
            try {
                $className = $this->getClassNameFromFile($file->getPathname());

                if (!$className || !class_exists($className)) {
                    continue;
                }

                $reflectionClass = new ReflectionClass($className);

                // Skip if class is abstract or interface
                if (
                    $reflectionClass->isAbstract() ||
                    $reflectionClass->isInterface()
                ) {
                    continue;
                }

                // Check for manual OutputMapper classes
                if (
                    $reflectionClass->implementsInterface(
                        OutputMapperInterface::class,
                    )
                ) {
                    $attributes = $reflectionClass->getAttributes(
                        OutputMapper::class,
                    );

                    if (!empty($attributes)) {
                        $attribute = $attributes[0]->newInstance();

                        // Skip if mapper is disabled
                        if ($attribute->enabled) {
                            $discoveredMappers[] = [
                                'class' => $className,
                                'priority' => $attribute->priority,
                                'type' => 'manual',
                            ];
                        }
                    }
                    continue;
                }

                // Check for Output Models with AutoMapFromDto attribute
                $autoMapAttributes = $reflectionClass->getAttributes(
                    AutoMapFromDto::class,
                );

                if (!empty($autoMapAttributes)) {
                    $attribute = $autoMapAttributes[0]->newInstance();

                    // Skip if mapping is disabled
                    if ($attribute->enabled) {
                        $discoveredMappers[] = [
                            'class' => $className,
                            'priority' => $attribute->priority,
                            'type' => 'auto',
                            'dto_class' => $attribute->dtoClass,
                            'field_map' => $attribute->fieldMap,
                            'exclude_fields' => $attribute->excludeFields,
                            'strategy' => $attribute->strategy,
                        ];
                    }
                }
            } catch (ReflectionException $e) {
                // Skip files that can't be reflected
                continue;
            }
        }

        // Sort by priority (higher priority first)
        usort(
            $discoveredMappers,
            fn($a, $b) => $b['priority'] <=> $a['priority'],
        );

        // Register discovered mappers
        foreach ($discoveredMappers as $mapper) {
            if ($mapper['type'] === 'manual') {
                $this->registerManualMapper(
                    $container,
                    $mapper['class'],
                    $mapper['priority'],
                );
            } else {
                $this->registerAutoMapper($container, $mapper);
            }
        }
    }

    /**
     * Registers a manual mapper as a service with the output_mapper tag
     */
    private function registerManualMapper(
        ContainerBuilder $container,
        string $className,
        int $priority,
    ): void {
        if ($container->hasDefinition($className)) {
            $definition = $container->getDefinition($className);
        } else {
            $definition = new Definition($className);
            $definition->setAutowired(true);
            $definition->setAutoconfigured(true);
            $container->setDefinition($className, $definition);
        }

        // Add the tag for automatic registration in OutputMapperRegistry
        $definition->addTag('app.output_mapper', ['priority' => $priority]);
    }

    /**
     * Registers an auto mapper (from Output Model with AutoMapFromDto attribute)
     */
    private function registerAutoMapper(
        ContainerBuilder $container,
        array $mapperConfig,
    ): void {
        // Create a unique service ID for the auto mapper
        $serviceId =
            'app.auto_mapper.'.
            str_replace("\\", '_', strtolower($mapperConfig['class']));

        // Create the GenericOutputMapper definition
        $definition = new Definition(GenericOutputMapper::class);
        $definition->setArguments([
            $mapperConfig['class'], // outputClass
            $mapperConfig['dto_class'], // dtoClass
            $mapperConfig['field_map'], // fieldMap
            $mapperConfig['exclude_fields'], // excludeFields
            $mapperConfig['strategy'], // strategy
        ]);
        $definition->setAutowired(false); // Don't autowire, we're providing all args
        $definition->setPublic(false);

        $container->setDefinition($serviceId, $definition);

        // Add the tag for automatic registration in OutputMapperRegistry
        $definition->addTag('app.output_mapper', [
            'priority' => $mapperConfig['priority'],
        ]);
    }

    /**
     * Extracts the fully qualified class name from a PHP file
     */
    private function getClassNameFromFile(string $filePath): ?string {
        $content = file_get_contents($filePath);

        if ($content === false) {
            return null;
        }

        // Extract namespace
        $namespace = null;
        if (preg_match('/^namespace\s+([^;]+);/m', $content, $matches)) {
            $namespace = trim($matches[1]);
        }

        // Extract class name
        $className = null;
        if (
            preg_match(
                '/^(?:final\s+|abstract\s+)?class\s+(\w+)/m',
                $content,
                $matches,
            )
        ) {
            $className = $matches[1];
        } elseif (
            preg_match(
                '/^(?:final\s+)?readonly\s+class\s+(\w+)/m',
                $content,
                $matches,
            )
        ) {
            $className = $matches[1];
        }

        if (!$className) {
            return null;
        }

        return $namespace ? $namespace."\\".$className : $className;
    }
}
