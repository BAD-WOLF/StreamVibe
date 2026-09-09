<?php
declare(strict_types=1);

namespace App;

use App\Infrastructure\DependencyInjection\OutputMapperDiscoveryPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 *
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function build(ContainerBuilder $container): void
    {
        // Register compiler pass for automatic output mapper discovery
        $container->addCompilerPass(new OutputMapperDiscoveryPass());
    }
}
